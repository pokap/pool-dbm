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

    /**
     * Return the result for given query builder object.
     *
     * @param mixed        $qb      Query or QueryBuilder object
     * @param integer|null $count   Number of items to retrieve (optional)
     * @param boolean      $hydrate Multi hydratation model (optional)
     *
     * @return mixed
     */
    protected function getQueryBuilderResult($qb, $count = null, $hydrate = true)
    {
        $result = $qb->execute();

        if ($hydrate) {
            $result = $this->hydrate((array) $result);
        }

        if ($count === 1) {
            $result = is_array($result) ? reset($result) : $result;
        }

        return $result;
    }

    /**
     * Returns the result for given query builder object.
     *
     * @param mixed $qb Query builder object
     *
     * @return mixed
     */
    protected function getQueryBuilderOneOrNullResult($qb)
    {
        return $this->getQueryBuilderResult($qb, 1);
    }

    /**
     * Multi hydratation model.
     *
     * @param array $objects
     *
     * @return array
     */
    private function hydrate(array $objects)
    {
        $pool = $this->manager->getPool();

        $models = array();
        foreach ($this->class->getFieldManagerNames() as $managerName) {
            $models[$managerName] = $this->class->getFieldMapping($managerName)->getName();
        }

        $data = array();
        $ids = array();
        foreach ($objects as $object) {
            $id = $this->class->getIdentifierValue($object);

            $data[$id][$this->class->getManagerIdentifier()] = $object;
            $ids[] = $id;
        }

        unset($models[$this->class->getManagerIdentifier()]);

        foreach ($models as $manager => $model) {
            foreach ($pool->getManager($manager)->getRepository($model)->findBy(array($this->class->getFieldIdentifier() => $ids)) as $object) {
                $id = $this->class->getIdentifierValue($object);

                $data[$id][$manager] = $object;
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
