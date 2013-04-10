<?php

namespace Pok\PoolDBM;

class Transaction
{
    private $manager;

    public function __construct(ModelManager $manager)
    {
        $this->manager = $manager;
    }

    public function beginTransaction()
    {
        // @todo move to a guesser-pass with name "transaction"
        foreach ($this->manager->getPool()->getIterator() as $manager) {
            if (method_exists('beginTransaction', $manager)) {
                $manager->beginTransaction();
            }
        }
    }

    public function persist($model)
    {
        $this->manager->persist($model);
    }

    public function remove($model)
    {
        $this->manager->remove($model);
    }

    public function commit()
    {
        // @todo move to a guesser-pass with name "transaction"
        foreach ($this->manager->getPool()->getIterator() as $manager) {
            if (method_exists('commit', $manager)) {
                $manager->commit();
            }
        }
    }

    public function rollback()
    {
        // @todo move to a guesser-pass with name "transaction"
        foreach ($this->manager->getPool()->getIterator() as $manager) {
            if (method_exists('rollback', $manager)) {
                $manager->rollback();
            }
        }
    }
}