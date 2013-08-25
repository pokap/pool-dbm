<?php

namespace Pok\PoolDBM;

use Doctrine\Common\Persistence\ObjectManager;

use Pok\PoolDBM\Persisters\ModelPersister;

class UnitOfWork
{
    /**
     * @var \SplObjectStorage
     */
    protected $models;

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
        $this->manager = $manager;

        $this->models     = new \SplObjectStorage();
        $this->persisters = array();
    }

    /**
     * @param null|mixed|array $models (optional)
     */
    public function commit($models = null)
    {
        $pool = $this->manager->getPool();
        /** @var ObjectManager[] $managers */
        $managers = $pool->getIterator();

        if (null === $models) {
            foreach ($managers as $manager) {
                $manager->flush();
            }

            if (!$this->models->count()) {
                return;
            }

            $this->models->rewind();
            while ($this->models->valid()) {
                $model = $this->models->current();
                $class = $this->manager->getClassMetadata(get_class($model));
                $managerName = $class->getManagerReferenceGenerator();

                $ref = call_user_func(array($model, 'get' . ucfirst($managerName)));

                $id = call_user_func(array($ref, 'get' . ucfirst($class->getIdentifierReference($managerName)->referenceField)));

                foreach ($this->models->getInfo() as $managerName) {
                    $this->saveSpecificModel($model, $managerName, $id);
                }

                $this->models->next();
            }

            // clear list
            $this->models = new \SplObjectStorage();
        } else {
            if (!is_array($models)) {
                $models = array($models);
            }

            foreach ($models as $model) {
                $class = $this->manager->getClassMetadata(get_class($model));
                $managerName = $class->getManagerReferenceGenerator();

                $ref = call_user_func(array($model, 'get' . ucfirst($managerName)));
                $pool->getManager($managerName)->flush($ref);

                $id = call_user_func(array($ref, 'get' . ucfirst($class->getIdentifierReference($managerName)->referenceField)));

                foreach ($class->getFieldManagerNames() as $managerName) {
                    $this->saveSpecificModel($model, $managerName, $id);
                }
            }
        }
    }

    /**
     * @param mixed $model
     *
     * @throws \RuntimeException
     */
    public function persist($model)
    {
        $class    = $this->manager->getClassMetadata(get_class($model));
        $managers = $class->getFieldManagerNames();
        $pool     = $this->manager->getPool();

        $managerName = $class->getManagerReferenceGenerator();
        $referenceModel = call_user_func(array($model, 'get' . ucfirst($managerName)));

        $pool->getManager($managerName)->persist($referenceModel);

        unset($managers[array_search($managerName, $managers)]);

        $this->models->attach($model, $managers);
    }

    /**
     * @param mixed $model
     */
    public function remove($model)
    {
        $class = $this->manager->getClassMetadata(get_class($model));
        $pool  = $this->manager->getPool();

        foreach ($class->getFieldManagerNames() as $managerName) {
            $pool->getManager($managerName)->remove(call_user_func(array($model, 'get' . ucfirst($managerName))));
        }
    }

    /**
     * @param mixed $model
     *
     * @return object
     */
    public function merge($model)
    {
        $class = $this->manager->getClassMetadata(get_class($model));
        $pool  = $this->manager->getPool();

        foreach ($class->getFieldManagerNames() as $managerName) {
            $pool->getManager($managerName)->merge(call_user_func(array($model, 'get' . ucfirst($managerName))));
        }
    }

    /**
     * @param mixed $model The model to detach.
     */
    public function detach($model)
    {
        $class = $this->manager->getClassMetadata(get_class($model));
        $pool  = $this->manager->getPool();

        foreach ($class->getFieldManagerNames() as $managerName) {
            $pool->getManager($managerName)->detach(call_user_func(array($model, 'get' . ucfirst($managerName))));
        }
    }

    /**
     * @param mixed $model
     */
    public function refresh($model)
    {
        $class = $this->manager->getClassMetadata(get_class($model));
        $pool  = $this->manager->getPool();

        foreach ($class->getFieldManagerNames() as $managerName) {
            $pool->getManager($managerName)->refresh(call_user_func(array($model, 'get' . ucfirst($managerName))));
        }
    }

    /**
     * @param mixed $model (optional)
     */
    public function clear($model = null)
    {
        if (null === $model) {
            $this->models = new \SplObjectStorage();

            foreach ($this->manager->getPool()->getIterator() as $manager) {
                if (method_exists($manager, 'clear')) {
                    $manager->clear(null);
                }
            }
        } else {
            $class = $this->manager->getClassMetadata(get_class($model));
            $pool  = $this->manager->getPool();

            if ($this->models->contains($model)) {
                $this->models->detach($model);
            }

            foreach ($class->getFieldManagerNames() as $managerName) {
                $manager = $pool->getManager($managerName);

                if (method_exists($manager, 'clear')) {
                    $manager->clear(call_user_func(array($model, 'get' . ucfirst($managerName))));
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

    /**
     * @param mixed  $model
     * @param string $managerName
     * @param mixed  $id
     */
    protected function saveSpecificModel($model, $managerName, $id)
    {
        $class = $this->manager->getClassMetadata(get_class($model));
        $pool = $this->manager->getPool();
        $modelManager = call_user_func(array($model, 'get' . ucfirst($managerName)));

        call_user_func(array($modelManager, 'set' . ucfirst($class->getIdentifierReference($managerName)->referenceField)), $id);

        $pool->getManager($managerName)->persist($modelManager);
        $pool->getManager($managerName)->flush();
    }
}
