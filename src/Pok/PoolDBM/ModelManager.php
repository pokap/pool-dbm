<?php

namespace Pok\PoolDBM;

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
     * Create a new query builder instance that is prepopulated for this model name.
     *
     * @param string $className
     * @param string $alias
     *
     * @return mixed
     */
    public function createQueryBuilder($className, $alias)
    {
        return $this->getIdentifierRepository($className)->createQueryBuilder($alias);
    }

    /**
     * @param mixed $model The model instance to remove.
     *
     * @throws \RuntimeException When manager is closed
     */
    public function persist($model)
    {
        $this->errorIfClosed();
        $this->unitOfWork->persist($model);
    }

    /**
     * @param mixed $model The model instance to remove.
     *
     * @throws \RuntimeException When manager is closed
     */
    public function remove($model)
    {
        $this->errorIfClosed();
        $this->unitOfWork->remove($model);
    }

    /**
     * @param mixed $model The model to refresh.
     *
     * @throws \RuntimeException When manager is closed
     */
    public function refresh($model)
    {
        $this->errorIfClosed();
        $this->unitOfWork->refresh($model);
    }

    /**
     * @param mixed $model The model to detach.
     */
    public function detach($model)
    {
        $this->unitOfWork->detach($model);
    }

    /**
     * @param mixed $model The detached model to merge into the persistence context.
     *
     * @return object The managed copy of the model
     *
     * @throws \RuntimeException When manager is closed
     */
    public function merge($model)
    {
        $this->errorIfClosed();

        return $this->unitOfWork->merge($model);
    }

    /**
     * Returns the model repository instance given by model name.
     *
     * @param string $className The name of the Model
     *
     * @return ModelRepository The repository
     */
    public function getRepository($className)
    {
        if (isset($this->repositories[$className])) {
            return $this->repositories[$className];
        }

        $metadata = $this->getClassMetadata($className);
        $customRepositoryClassName = $metadata->getCustomRepositoryClassName();

        if ($customRepositoryClassName !== null) {
            $repository = new $customRepositoryClassName($this, $this->unitOfWork, $metadata);
        } else {
            $repository = new ModelRepository($this, $this->unitOfWork, $metadata);
        }

        $this->repositories[$className] = $repository;

        return $repository;
    }

    /**
     * Hydrate a model.
     *
     * @param mixed $model
     * @param array $fields List of fields prime (optional)
     *
     * @return null|object|object[]
     */
    public function hydrate($model, array $fields = array())
    {
        if (is_array($model)) {
            return $this->getRepository(get_class(reset($model)))->hydrate($model, $fields);
        }

        $row = $this->getRepository(get_class($model))->hydrate(array($model), $fields);

        if (empty($row)) {
            return null;
        }

        return $row[0];
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
     * @param mixed $model
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
     * Executes a function in a transaction.
     *
     * If an exception occurs during execution of the function or flushing or transaction commit,
     * the transaction is rolled back, close and exception re-throw.
     *
     * If the manager who throw the exception do not support the transaction, he will try to remove
     * model saved with success.
     *
     * @param callable $func The function to execute transactional
     *
     * @return mixed The non-empty value returned from the closure or model instead
     *
     * @throws \Exception When transaction fail
     */
    public function transactional($func)
    {
        $transaction = new Transaction($this);
        $transaction->beginTransaction();

        try {
            $return = call_user_func($func, $transaction);

            $this->flush();
            $transaction->commit();

            return $return ?: true;
        } catch (\Exception $e) {
            $this->close();
            $transaction->rollback();

            throw $e;
        }
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
     * @throws \RuntimeException When the ModelManager is closed
     */
    private function errorIfClosed()
    {
        if ($this->closed) {
            throw new \RuntimeException('Model manager is closed.');
        }
    }
}
