<?php

namespace Pok\PoolDBM;

use Pok\PoolDBM\Persisters\ModelPersister;

class UnitOfWork
{
    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @var ModelPersister[]
     */
    private $persisters;

    /**
     * Constructor.
     *
     * @param ModelManager $manager
     */
    public function __construct(ModelManager $manager)
    {
        $this->manager    = $manager;
        $this->persisters = array();
    }

    /**
     * @param null|object|array $models  (optional)
     * @param array             $options (optional)
     */
    public function commit($models = null, array $options = array())
    {
        if (null === $models) {
            foreach ($this->manager->getPool()->getIterator() as $manager) {
                $manager->flush();
            }
        } else {
            foreach ((array) $models as $model) {
                $class = $this->manager->getClassMetadata(get_class($model));
                $pool  = $this->manager->getPool();

                foreach ($class->getFieldManagerNames() as $managerName) {
                    $pool->getManager($managerName)->flush($model->{'get' . ucfirst($managerName)}(), $options);
                }
            }
        }
    }

    /**
     * @param object $model
     */
    public function persist($model)
    {
        $class    = $this->manager->getClassMetadata(get_class($model));
        $managers = $class->getFieldManagerNames();
        $pool     = $this->manager->getPool();

        $managerName = $class->getManagerReferenceGenerator();
        $referenceModel = $model->{'get' . ucfirst($managerName)}();

        $pool->getManager($managerName)->persist($referenceModel);

        unset($managers[$managerName]);

        foreach ($managers as $managerName) {
            $managerModel = $model->{'get' . ucfirst($managerName)}();
            $managerModel->setId($referenceModel->getId());

            $pool->getManager($managerName)->persist($managerModel);
        }
    }

    /**
     * @param object $model
     */
    public function remove($model)
    {
        $class = $this->manager->getClassMetadata(get_class($model));
        $pool  = $this->manager->getPool();

        foreach ($class->getFieldManagerNames() as $managerName) {
            $pool->getManager($managerName)->remove($model->{'get' . ucfirst($managerName)}());
        }
    }

    /**
     * @param object $model
     *
     * @return object
     */
    public function merge($model)
    {
        $class = $this->manager->getClassMetadata(get_class($model));
        $pool  = $this->manager->getPool();

        foreach ($class->getFieldManagerNames() as $managerName) {
            $pool->getManager($managerName)->merge($model->{'get' . ucfirst($managerName)}());
        }
    }

    /**
     * @param object $model The model to detach.
     */
    public function detach($model)
    {
        $class = $this->manager->getClassMetadata(get_class($model));
        $pool  = $this->manager->getPool();

        foreach ($class->getFieldManagerNames() as $managerName) {
            $pool->getManager($managerName)->detach($model->{'get' . ucfirst($managerName)}());
        }
    }

    /**
     * @param object $model
     */
    public function refresh($model)
    {
        $class = $this->manager->getClassMetadata(get_class($model));
        $pool  = $this->manager->getPool();

        foreach ($class->getFieldManagerNames() as $managerName) {
            $pool->getManager($managerName)->refresh($model->{'get' . ucfirst($managerName)}());
        }
    }

    /**
     * @param string $model (optional)
     */
    public function clear($model = null)
    {
        if (null === $model) {
            foreach ($this->manager->getPool()->getIterator() as $manager) {
                $manager->clear(null);
            }
        } else {
            $class = $this->manager->getClassMetadata(get_class($model));
            $pool  = $this->manager->getPool();

            foreach ($class->getFieldManagerNames() as $managerName) {
                $pool->getManager($managerName)->clear($model->{'get' . ucfirst($managerName)}());
            }
        }
    }

    /**
     * Get the multi-model persister instance for the given multi-model name.
     *
     * @param string $modelName
     *
     * @return DocumentPersister
     */
    public function getModelPersister($modelName)
    {
        if (!isset($this->persisters[$modelName])) {
            $class = $this->manager->getClassMetadata($modelName);

            $this->persisters[$modelName] = new ModelPersister($this->manager, $this, $class);
        }

        return $this->persisters[$modelName];
    }

    /**
     * Set the multi-model persister instance to use for the given multi-model .
     *
     * @param string         $modelName
     * @param ModelPersister $persister
     */
    public function setModelPersister($modelName, ModelPersister $persister)
    {
        $this->persisters[$modelName] = $persister;
    }

    /**
     * @param string $className
     * @param array  $data
     *
     * @return object The model instance.
     */
    public function createModel($className, $data)
    {
        $class = $this->manager->getClassMetadata($className);
        $model = $class->newInstance();

        foreach ($data as $managerName => $value) {
            $model->{'set' . ucfirst($managerName)}($value);
        }

        return $model;
    }
}
