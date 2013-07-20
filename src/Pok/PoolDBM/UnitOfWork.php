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
            if (!is_array($models)) {
                $models = array($models);
            }

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
     *
     * @throws \RuntimeException
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
            if (!is_object($managerModel)) {
                throw new \RuntimeException(sprintf('Getter manager "%s" must be returns object, "%s" given by model "%s".', $managerName, get_class($model), gettype($managerModel)));
            }

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
                if (method_exists($manager, 'clear')) {
                    $manager->clear(null);
                }
            }
        } else {
            $class = $this->manager->getClassMetadata(get_class($model));
            $pool  = $this->manager->getPool();

            foreach ($class->getFieldManagerNames() as $managerName) {
                $manager = $pool->getManager($managerName);

                if (method_exists($manager, 'clear')) {
                    $manager->clear($model->{'get' . ucfirst($managerName)}());
                }
            }
        }
    }

    /**
     * Get the multi-model persister instance for the given multi-model name.
     *
     * @param string $modelName
     *
     * @return ModelPersister
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
     *
     * @throws \RuntimeException
     */
    public function createModel($className, $data)
    {
        $model = new $className;

        foreach ($data as $managerName => $value) {
            $is_collection = is_array($value);

            if ($is_collection) {
                $method_name = 'get' . ucfirst($managerName);
            } else {
                $method_name = 'set' . ucfirst($managerName);
            }

            if (!method_exists($model, $method_name)) {
                throw new \RuntimeException(sprintf('Method "%s" does not exist in "%s" class.', $method_name, $className));
            }

            if ($is_collection) {
                foreach ($value as $element) {
                    $model->$method_name()->add($element);
                }
            } else {
                $model->$method_name($value);
            }
        }

        return $model;
    }
}
