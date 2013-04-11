<?php

namespace Pok\PoolDBM\Persisters;

use Pok\PoolDBM\ModelManager;
use Pok\PoolDBM\UnitOfWork;
use Pok\PoolDBM\Mapping\ClassMetadata;

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
        $this->uow = $uow;
        $this->class = $class;
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

        $models = array();
        foreach ($this->class->getFieldManagerNames() as $manager) {
            $models[$manager] = $this->class->getFieldMapping($manager)->getName();
        }

        $result = array();
        $result[$manager_id] = $pool->getManager($manager_id)->getRepository($models[$manager_id])->findOneBy($criteria);
        unset($models[$manager_id]);

        if (!$result[$manager_id]) {
            return null;
        }

        $id = $this->class->getIdentifierValue($result[$manager_id]);

        foreach ($models as $manager => $model) {
            $result[$manager] = $pool->getManager($manager)->getRepository($model)->find($id);
        }

        return $this->createModel($result);
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

            $data[$id][$manager_id] = $object;
        }

        $ids = array_keys($data);
        if (empty($ids)) {
            return null;
        }

        unset($models[$manager_id]);

        foreach ($models as $manager => $model) {
            $methodFind = $this->class->getFieldMapping($manager)->getRepositoryMethod();

            if ($methodFind) {
                foreach ($pool->getManager($manager)->getRepository($model)->$methodFind($ids) as $object) {
                    $id = $this->class->getIdentifierValue($object);

                    $data[$id][$manager] = $object;
                }
            } else {
                trigger_error(sprintf('findOneBy in ModelPersister::loadAll context is depreciate. Define repository-method for "%s" manager model, see mapping for "%s".', $manager, $this->class->getName()), E_USER_DEPRECATED);

                foreach ($ids as $id) {
                    $object = $pool->getManager($manager)->getRepository($model)->findOneBy(array($this->class->getFieldIdentifier() => $id));

                    $id = $this->class->getIdentifierValue($object);

                    $data[$id][$manager] = $object;
                }
            }
        }

        $result = array();
        foreach ($ids as $id) {
            $result[] = $this->createModel($data[$id]);
        }

        return $result;
    }

    /**
     * @param  array  $data
     * @return object
     */
    private function createModel(array $data)
    {
        if (empty($data)) {
            return null;
        }

        return $this->uow->createModel($this->class->getName(), $data);
    }
}
