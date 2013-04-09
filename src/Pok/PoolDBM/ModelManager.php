<?php

namespace Pok\PoolDBM;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Common\Persistence\ObjectManager;

use Pok\PoolDBM\Mapping\ClassMetadataFactory;
use Pok\PoolDBM\Manager\PoolInterface;

/**
 * Class ModelManager.
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
class ModelManager implements ObjectManager
{
    /**
     * The metadata factory, used to retrieve the ODM metadata of document classes.
     *
     * @var ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * @var MappingDriverChain 
     */
    private $metadataDriverImpl;

    /**
     * The ModelRepository instances.
     *
     * @var array
     */
    private $repositories = array();

    /**
     * @var PoolInterface
     */
    protected $pool;

    /**
     * The UnitOfWork used to coordinate object-level transactions.
     *
     * @var UnitOfWork
     */
    protected $unitOfWork;

    /**
     * Whether the ModelManager is closed or not.
     *
     * @var bool
     */
    private $closed = false;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * Constructor.
     *
     * @param PoolInterface        $pool
     * @param ClassMetadataFactory $metadataFactory (optional)
     * @param UnitOfWork           $unitOfWork      (optional)
     */
    public function __construct(PoolInterface $pool, ClassMetadataFactory $metadataFactory = null, UnitOfWork $unitOfWork = null)
    {
        $this->pool = $pool;

        $this->metadataFactory = $metadataFactory? : new ClassMetadataFactory();
        $this->metadataFactory->setManager($this);

        $this->unitOfWork = $unitOfWork? : new UnitOfWork($this);
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    /**
     * @param MappingDriverChain $driverChain
     */
    public function setMetadataDriverImpl(MappingDriverChain $driverChain)
    {
        $this->metadataDriverImpl = $driverChain;
    }

    /**
     * @return MappingDriverChain
     */
    public function getMetadataDriverImpl()
    {
        return $this->metadataDriverImpl;
    }

    /**
     * @return PoolInterface
     */
    public function getPool()
    {
        return $this->pool;
    }

    /**
     * {@inheritDoc}
     */
    public function initializeObject($obj)
    {
    }

    /**
     * @return UnitOfWork
     */
    public function getUnitOfWork()
    {
        return $this->unitOfWork;
    }

    /**
     * {@inheritDoc}
     *
     * @return Mapping\ClassMetadata
     */
    public function getClassMetadata($className)
    {
        return $this->metadataFactory->getMetadataFor($className);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder($className, $alias)
    {
        return $this->getIdentifierRepository($className)->createQueryBuilder($alias);
    }

    /**
     * @param object $model The document instance to remove.
     *
     * @throws \RuntimeException When manager is closed
     */
    public function persist($model)
    {
        $this->errorIfClosed();
        $this->unitOfWork->persist($model);
    }

    /**
     * @param object $model The document instance to remove.
     *
     * @throws \RuntimeException When manager is closed
     */
    public function remove($model)
    {
        $this->errorIfClosed();
        $this->unitOfWork->remove($model);
    }

    /**
     * @param object $model The document to refresh.
     *
     * @throws \RuntimeException When manager is closed
     */
    public function refresh($model)
    {
        $this->errorIfClosed();
        $this->unitOfWork->refresh($model);
    }

    /**
     * @param object $model The document to detach.
     */
    public function detach($model)
    {
        $this->unitOfWork->detach($model);
    }

    /**
     * @param object $model The detached document to merge into the persistence context.
     *
     * @return object The managed copy of the document
     *
     * @throws \RuntimeException When manager is closed
     */
    public function merge($model)
    {
        $this->errorIfClosed();

        return $this->unitOfWork->merge($model);
    }

    /**
     * @param string $modelName  The name of the Model.
     * @return DocumentRepository  The repository.
     */
    public function getRepository($modelName)
    {
        if (isset($this->repositories[$modelName])) {
            return $this->repositories[$modelName];
        }

        $metadata = $this->getClassMetadata($modelName);
        $customRepositoryClassName = $metadata->getCustomRepositoryClassName();

        if ($customRepositoryClassName !== null) {
            $repository = new $customRepositoryClassName($this, $this->unitOfWork, $metadata);
        } else {
            $repository = new ModelRepository($this, $this->unitOfWork, $metadata);
        }

        $this->repositories[$modelName] = $repository;

        return $repository;
    }

    /**
     * @param null|array|object $model   (optional)
     * @param array             $options (optional)
     *
     * @throws \RuntimeException When manager is closed
     */
    public function flush($model = null, array $options = array())
    {
        $this->errorIfClosed();
        $this->unitOfWork->commit($model, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function find($className, $id)
    {
        return $this->getRepository($className)->find($id);
    }

    /**
     * Clears the managers of ModelManager. All models that are currently managed in this manager become detached.
     *
     * @param string|null $modelName
     */
    public function clear($modelName = null)
    {
        $this->unitOfWork->clear($modelName);
    }

    /**
     * Close manager with pool managers.
     */
    public function close()
    {
        $this->clear();
        $this->closed = true;
    }

    /**
     * Determines whether a model instance is managed in this ModelManager.
     *
     * @param object $model
     *
     * @return boolean TRUE if this ModelManager currently manages the given document, FALSE otherwise.
     */
    public function contains($model)
    {
        $class = $this->getClassMetadata(get_class($model));

        foreach ($class->getFieldManagerNames() as $manager) {
            if (!$this->pool->getManager($manager)->contains($model->{'get' . ucfirst($manager)}())) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sets the EventManager used by the ModelManager.
     *
     * @param EventManager $eventManager
     */
    public function setEventManager(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * Returns if an EventManager used by the ModelManager.
     *
     * @@return boolean
     */
    public function hasEventManager()
    {
        return null !== $this->eventManager;
    }

    /**
     * Gets the EventManager used by the ModelManager.
     *
     * @return EventManager
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }

    /**
     * Returns repository model given by identifier.
     *
     * @param string $className
     *
     * @return mixed
     */
    protected function getIdentifierRepository($className)
    {
        $class = $this->getClassMetadata($className);

        return $this->pool->getManager($class->getManagerIdentifier())->getRepository($class->getFieldMapping($class->getManagerIdentifier())->getName());
    }

    /**
     * Throws an exception if the ModelManager is closed or currently not active.
     *
     * @throws ModelException If the ModelManager is closed.
     */
    private function errorIfClosed()
    {
        if ($this->closed) {
            throw new \RuntimeException('Model manager is closed.');
        }
    }
}
