<?php

namespace Pok\PoolDBM\Persisters;

use Pok\PoolDBM\ModelManager;
use Pok\PoolDBM\Mapping\ClassMetadata;
use Pok\PoolDBM\UnitOfWork;

class ModelPersister
{
    /**
     * The ModelManager instance.
     *
     * @var ModelManager
     */
    private $manager;

    /**
     * The UnitOfWork instance.
     *
     * @va UnitOfWork
     */
    private $uow;

    /**
     * The ClassMetadata instance for the multi-model type being persisted.
     *
     * @var ClassMetadata
     */
    private $class;

    /**
     * Constructor.
     */
    public function __construct(ModelManager $manager, UnitOfWork $uow, ClassMetadata $class)
    {
        $this->manager = $manager;
        $this->uow     = $uow;
        $this->class   = $class;
    }

    /**
     * Gets the ClassMetadata instance of the multi-model class this persister is used for.
     *
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->class;
    }

    /**
     * Loads an multi-model by a list of field criteria.
     *
     * @param mixed $criteria
     *
     * @return null|object The loaded and managed model instance or NULL if the multi-model can not be found
     */
    public function load($criteria)
    {
        $pool       = $this->manager->getPool();
        $manager_id = $this->class->getManagerIdentifier();

        if (!is_array($criteria)) {
            $criteria = array($this->class->getFieldIdentifier() => $criteria);
        }

        $model = $pool->getManager($manager_id)
            ->getRepository($this->class->getFieldMapping($manager_id)->getName())
            ->findOneBy($criteria);

        if (null === $model) {
            return null;
        }

        $builder = new ModelBuilder($this->manager, $this->uow, $this->class);

        return $builder->build($model, $manager_id);
    }

    /**
     * Loads a list of model by a list of field criteria.
     *
     * @param array   $criteria
     * @param array   $orderBy
     * @param integer $limit    (optional)
     * @param integer $offset   (optional)
     *
     * @return array
     */
    public function loadAll(array $criteria = array(), array $orderBy = null, $limit = null, $offset = null)
    {
        $pool       = $this->manager->getPool();
        $manager_id = $this->class->getManagerIdentifier();

        $models = array();
        foreach ($this->class->getFieldManagerNames() as $manager) {
            $models[$manager] = $this->class->getFieldMapping($manager)->getName();
        }

        $data = array();
        foreach ($pool->getManager($manager_id)->getRepository($models[$manager_id])->findBy($criteria, $orderBy, $limit, $offset) as $object) {
            $id = $this->class->getIdentifierValue($object);

            $data[$id] = $object;
        }

        if (empty($data)) {
            return array();
        }

        $builder = new ModelBuilder($this->manager, $this->uow, $this->class);

        return $builder->buildAll($data, $manager_id);
    }
}
