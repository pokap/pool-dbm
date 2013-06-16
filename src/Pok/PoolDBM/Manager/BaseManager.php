<?php

namespace Pok\PoolDBM\Manager;

use Pok\PoolDBM\ModelManager;

/**
 * Base manager for doctrine multi-model
 */
class BaseManager
{
    /**
     * @var ModelManager
     */
    protected $manager;

    /**
     * @var string
     */
    protected $class;

    /**
     * Constructor
     *
     * @param string       $class   Name of model class
     * @param ModelManager $manager Model manager
     */
    public function __construct($class, ModelManager $manager)
    {
        $this->class   = $class;
        $this->manager = $manager;
    }

    /**
     * Returns a new non-managed model.
     *
     * @return mixed
     */
    public function create()
    {
        return new $this->class;
    }

    /**
     * Saves an model.
     *
     * @param mixed $model Model to save
     * @param bool  $sync  Synchronize directly with database
     *
     * @throws \RuntimeException
     */
    public function save($model, $sync = false)
    {
        if (!$model instanceof $this->class) {
            throw new \RuntimeException(sprintf('Manager "%s" is unable to save model "%s"', get_class($this), get_class($model)));
        }

        $this->manager->persist($model);

        if ($sync) {
            $this->manager->flush();
        }
    }

    /**
     * Flushes all changes to objects that have been queued up to now to the database.
     */
    public function flush()
    {
        $this->manager->flush();
    }

    /**
     * Deletes a model.
     *
     * @param mixed $model Model to save
     * @param bool  $sync  Synchronize directly with database
     *
     * @throws \RuntimeException
     */
    public function delete($model, $sync = false)
    {
        if (!$model instanceof $this->class) {
            throw new \RuntimeException(sprintf('Manager "%s" is unable to delete model "%s"', get_class($this), get_class($model)));
        }

        $this->manager->remove($model);

        if ($sync) {
            $this->manager->flush();
        }
    }

    /**
     * Clears the managers of ModelManager. All models that are currently managed in this manager become detached.
     *
     * @param string $modelName if given, only entities of this type will get detached
     */
    public function clear($modelName = null)
    {
        $this->manager->clear($modelName);
    }

    /**
     * Returns a "fresh" model by identifier.
     *
     * @param integer $id Model identifier
     *
     * @return object
     */
    public function find($id)
    {
        return $this->getRepository()->find($id);
    }

    /**
     * Returns entities according to criteria.
     *
     * @param array        $criteria An array of key/value matching AND conditions with field/value
     * @param null|array   $order    An array of key/value matching field/order (optional)
     * @param null|integer $limit    Maximum number of entities to return (optional)
     * @param null|integer $offset   Starting index to start from (optional)
     *
     * @return array An array of models
     */
    public function findBy(array $criteria, array $order = null, $limit = null, $offset = null)
    {
        return $this->getRepository()->findBy($criteria, $order, $limit, $offset);
    }

    /**
     * Returns one model according to criteria.
     *
     * @param array $criteria An array of key/value matching AND conditions with field/value
     *
     * @return mixed|null
     */
    public function findOneBy(array $criteria)
    {
        return $this->getRepository()->findOneBy($criteria);
    }

    /**
     * Returns all models.
     *
     * @return array
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Returns the entity repository
     *
     * @return \Pok\PoolDBM\ModelRepository
     */
    protected function getRepository()
    {
        return $this->manager->getRepository($this->class);
    }
}
