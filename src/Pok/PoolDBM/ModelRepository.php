<?php

namespace Pok\PoolDBM;

class ModelRepository
{
    /**
     * @var ModelManager
     */
    protected $manager;

    /**
     * @var UnitOfWork
     */
    protected $uow;

    /**
     * @var Mapping\ClassMetadata 
     */
    protected $class;

    /**
     * Construtor.
     *
     * @param ModelManager          $manager
     * @param UnitOfWork            $uow
     * @param Mapping\ClassMetadata $class
     */
    public function __construct(ModelManager $manager, UnitOfWork $uow, Mapping\ClassMetadata $class)
    {
        $this->manager = $manager;
        $this->uow     = $uow;
        $this->class   = $class;
    }

    public function createQueryBuilder($alias)
    {
        return $this->manager->createQueryBuilder($this->getClassName(), $alias);
    }

    /**
     * {@inheritDoc}
     */
    public function find($id)
    {
        return $this->uow->getModelPersister($this->getClassName())->load($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findAll()
    {
        return $this->findBy(array());
    }

    /**
     * {@inheritDoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->uow->getModelPersister($this->getClassName())->loadAll($criteria, $orderBy, $limit, $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function findOneBy(array $criteria)
    {
        return $this->find($criteria);
    }

    /**
     * {@inheritDoc}
     */
    public function getClassName()
    {
        return $this->class->getName();
    }
}
