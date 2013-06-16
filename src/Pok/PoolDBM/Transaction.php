<?php

namespace Pok\PoolDBM;

/**
 * Transaction
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
class Transaction
{
    const QUEUE_ACTION_PERSIST = 0;
    const QUEUE_ACTION_REMOVE  = 1;

    /**
     * @var ModelManager
     */
    protected $manager;

    private $queueActions;
    private $queueManagerNames;
    private $queueModels;
    private $queueIds;

    /**
     * Constructor.
     *
     * @param ModelManager $manager
     */
    public function __construct(ModelManager $manager)
    {
        $this->manager = $manager;

        $this->cleanQueue();
    }

    /**
     * Starts a transaction on the underlying database connection.
     *
     * @return void
     */
    public function beginTransaction()
    {
        $this->launch(function ($manager) {
            $manager->beginTransaction();
        });
    }

    /**
     * Saves a model.
     *
     * @param object $model
     */
    public function persist($model)
    {
        $class    = $this->manager->getClassMetadata(get_class($model));
        $managers = $class->getFieldManagerNames();
        $pool     = $this->manager->getPool();
        $priority = $pool->getPriority('transaction');

        $id = null;

        if ($class->hasManagerReferenceGenerator()) {
            $managerName = $class->getManagerReferenceGenerator();
            $referenceModel = $model->{'get' . ucfirst($managerName)}();

            $pool->getManager($managerName)->persist($referenceModel);
            $id = $referenceModel->getId();

            unset($managers[$managerName]);
        }

        foreach ($managers as $key => $managerName) {
            if (isset($priority[$managerName])) {
                $this->doPersist($managerName, $model, $id);

                unset($managers[$key]); // dereference index
            }
        }

        foreach ($managers as $managerName) {
            $this->addQueue(self::QUEUE_ACTION_PERSIST, $managerName, $model, $id);
        }
    }

    /**
     * Deletes a model.
     *
     * @param object $model
     */
    public function remove($model)
    {
        $class    = $this->manager->getClassMetadata(get_class($model));
        $managers = $class->getFieldManagerNames();
        $priority = $this->manager->getPool()->getPriority('transaction');

        foreach ($managers as $key => $managerName) {
            if (isset($priority[$managerName])) {
                $this->manager->remove($model);

                unset($managers[$key]); // dereference index
            }
        }

        foreach ($managers as $managerName) {
            $this->addQueue(self::QUEUE_ACTION_REMOVE, $managerName, $model);
        }
    }

    /**
     * Commits a transaction on the underlying database connection.
     *
     * @return void
     */
    public function commit()
    {
        $this->launch(function ($manager) {
            $manager->commit();
        });

        $this->executeQueue();
        $this->manager->flush();
    }

    /**
     * Performs a rollback on the underlying database connection.
     *
     * @return void
     */
    public function rollback()
    {
        $this->cleanQueue();

        $this->launch(function ($manager) {
            $manager->rollback();
        });
    }

    /**
     * Saves an model.
     *
     * @param string     $managerName
     * @param object     $model
     * @param null|mixed $id
     */
    protected function doPersist($managerName, $model, $id = null)
    {
        $managerModel = $model->{'get' . ucfirst($managerName)}();

        if ($id) {
            $managerModel->setId($id);
        }

        $this->manager->getPool()->getManager($managerName)->persist($managerModel);
    }

    /**
     * Launch function for each manager in transaction priority.
     *
     * @param \Closure $func
     */
    protected function launch(\Closure $func)
    {
        foreach ($this->manager->getPool()->getPriority('transaction') as $managerName) {
            $func($this->manager->getPool()->getManager($managerName));
        }
    }

    /**
     * Add action with model reference in queue.
     *
     * @param integer    $action
     * @param string     $managerName
     * @param object     $model
     * @param null|mixed $id
     *
     * @throws \InvalidArgumentException
     */
    protected function addQueue($action, $managerName, $model, $id = null)
    {
        if (!in_array($action, array( self::QUEUE_ACTION_PERSIST, self::QUEUE_ACTION_REMOVE))) {
            throw new \InvalidArgumentException(sprintf('Action unknown, manager name : "%s".', $managerName));
        }

        $this->queueActions[]      = $action;
        $this->queueManagerNames[] = $managerName;
        $this->queueModels[]       = $model;
        $this->queueIds[]           = $id;
    }

    /**
     * Execute all data in queue.
     */
    protected function executeQueue()
    {
        foreach ($this->queueActions as $key => $action) {
            if (self::QUEUE_ACTION_PERSIST === $action) {
                $this->doPersist($this->queueManagerNames[$key], $this->queueModels[$key], $this->queueIds[$key]);
            } else {
                $this->manager->getPool()->getManager($this->queueManagerNames[$key])->remove($this->queueModels[$key]);
            }
        }

        $this->cleanQueue();
    }

    /**
     * Clean all queues.
     */
    protected function cleanQueue()
    {
        $this->queueActions      = array();
        $this->queueManagerNames = array();
        $this->queueModels       = array();
        $this->queueIds          = array();
    }
}
