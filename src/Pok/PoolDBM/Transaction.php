<?php

namespace Pok\PoolDBM;

class Transaction
{
    private $manager;
    private $queueActions;
    private $queueModels;

    const ACTION_PERSIST = 0;
    const ACTION_REMOVE  = 1;

    public function __construct(ModelManager $manager)
    {
        $this->manager = $manager;

        $this->queueActions = array();
        $this->queueModels  = array();
    }

    public function beginTransaction()
    {
        foreach ($this->manager->getPool()->getIterator() as $manager) {
            if (method_exists('beginTransaction', $manager)) {
                $manager->beginTransaction();
            }
        }
    }

    public function persist($model)
    {
//        $this->queueActions[] = self::ACTION_PERSIST;
//        $this->queueModels[]  = $model;

        $this->manager->persist($model);
    }

    public function remove($model)
    {
//        $this->queueActions[] = self::ACTION_REMOVE;
//        $this->queueModels[]  = $model;

        $this->manager->remove($model);
    }

    public function commit()
    {
        foreach ($this->manager->getPool()->getIterator() as $manager) {
            if (method_exists('commit', $manager)) {
                $manager->commit();
            }
        }
    }

    public function rollback()
    {
//        for ($i = count($this->queueActions); $i > 0; $i--) {
//            if ($this->queueActions[$i] === self::ACTION_PERSIST) {
//                $this->manager->remove($this->queueModels[$i]);
//            } else {
//                $this->manager->persist($this->queueModels[$i]);
//            }
//        }

        foreach ($this->manager->getPool()->getIterator() as $manager) {
            if (method_exists('rollback', $manager)) {
                $manager->rollback();
            }
        }

        $this->queueActions = array();
        $this->queueModels  = array();
    }
}