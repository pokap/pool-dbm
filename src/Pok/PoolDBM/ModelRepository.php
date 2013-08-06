<?php

namespace Pok\PoolDBM;

use Pok\PoolDBM\Persisters\ModelBuilder;

/**
 * Model repository retrieve repository per manager type, and integrates tools for hydrate result.
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
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
     * @var ModelBuilder
     */
    protected $modelBuilder;

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

        $this->modelBuilder = new ModelBuilder($manager, $uow, $class);
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
     * @param boolean      $except  Keep object and ignore field adding in select query (optional)
     *
     * @return mixed
     */
    protected function getQueryBuilderResult($qb, $count = null, $hydrate = true, $except = false)
    {
        $result = $qb->execute();

        if ($except) {
            foreach ($result as $key => $value) {
                foreach ($value as $field => $data) {
                    if (is_int($field)) {
                        $result[$key] = $data;

                        continue 2;
                    }
                }
            }
        }

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
     * Multiple hydration model.
     *
     * @param array $objects
     *
     * @return array
     */
    protected function hydrate(array $objects)
    {
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
            $this->modelBuilder->loaderModels($this->class, $manager, $ids, function ($id, $object) use (&$data, $manager) {
                $data[$id][$manager] = $object;
            });
        }

        $result = array();
        foreach ($ids as $id) {
            $result[] = $this->modelBuilder->createModel($this->class->getName(), $data[$id]);
        }

        return $result;
    }
}
