<?php

namespace Pok\PoolDBM\Persisters;

use Doctrine\Common\Collections\ArrayCollection;
use Pok\PoolDBM\ModelManager;
use Pok\PoolDBM\Mapping\ClassMetadata;
use Pok\PoolDBM\PersistentCollection;
use Pok\PoolDBM\UnitOfWork;

class ModelPersister
{
    /**
     * The ModelManager instance.
     *
     * @var ModelManager
     */
    private $manager;

    /**
     * The UnitOfWork instance.
     *
     * @va UnitOfWork
     */
    private $uow;

    /**
     * The ClassMetadata instance for the multi-model type being persisted.
     *
     * @var ClassMetadata
     */
    private $class;

    /**
     * Constructor.
     */
    public function __construct(ModelManager $manager, UnitOfWork $uow, ClassMetadata $class)
    {
        $this->manager = $manager;
        $this->uow     = $uow;
        $this->class   = $class;
    }

    /**
     * Gets the ClassMetadata instance of the multi-model class this persister is used for.
     *
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->class;
    }

    /**
     * Loads an multi-model by a list of field criteria.
     *
     * @param mixed $criteria
     *
     * @return null|object The loaded and managed model instance or NULL if the multi-model can not be found
     */
    public function load($criteria)
    {
        $pool       = $this->manager->getPool();
        $manager_id = $this->class->getManagerIdentifier();

        if (!is_array($criteria)) {
            $criteria = array($this->class->getFieldIdentifier() => $criteria);
        }

        $models = array();
        foreach ($this->class->getFieldManagerNames() as $manager) {
            $models[$manager] = $this->class->getFieldMapping($manager)->getName();
        }

        $result = array();
        $result[$manager_id] = $pool->getManager($manager_id)->getRepository($models[$manager_id])->findOneBy($criteria);

        if (!$result[$manager_id]) {
            return null;
        }

        $referenceId = $this->class->getIdentifierValue($result[$manager_id]);
        $collections = $this->getManagersPerCollection($result[$manager_id]);

        if (empty($collections)) {
            foreach ($this->buildAndSortIdPerManager($referenceId, $models) as $manager => $id) {
                $result[$manager] = $pool->getManager($manager)->getRepository($models[$manager])->find($id);
            }
        } else {
            foreach ($this->buildAndSortIdPerManager($referenceId, $models, $collections) as $manager => $ids) {
                $data = $this->relayLoadModels($manager, $models[$manager], $ids);

                $result[$manager] = $data[$referenceId];

                foreach ($collections as $field => $coll) {
                    if ($this->class->isSingleValuedAssociation($field)) {
                        $subresult = array();
                        foreach ($coll as $id => $managers) {
                            foreach ($managers as $manager) {
                                $subresult[$manager] = $data[$id];
                            }
                        }

                        $result[$field] = $this->createModel($this->manager->getClassMetadata($this->class->getAssociationTargetClass($field))->getName(), $subresult);
                    }
                    else {
                        $result[$field] = new ArrayCollection();

                        foreach ($coll as $id => $managers) {
                            $subresult = array();

                            foreach ($managers as $manager) {
                                $subresult[$manager] = $data[$id];
                            }

                            $result[$field]->add($this->createModel($this->manager->getClassMetadata($this->class->getAssociationTargetClass($field))->getName(), $subresult));
                        }
                    }
                }
            }
        }

        return $this->createModel($this->class->getName(), $result);
    }

    /**
     * Loads a list of model by a list of field criteria.
     *
     * @param array   $criteria
     * @param array   $orderBy
     * @param integer $limit    (optional)
     * @param integer $offset   (optional)
     *
     * @return array
     */
    public function loadAll(array $criteria = array(), array $orderBy = null, $limit = null, $offset = null)
    {
        $pool       = $this->manager->getPool();
        $manager_id = $this->class->getManagerIdentifier();

        $models = array();
        foreach ($this->class->getFieldManagerNames() as $manager) {
            $models[$manager] = $this->class->getFieldMapping($manager)->getName();
        }

        $data = array();
        foreach ($pool->getManager($manager_id)->getRepository($models[$manager_id])->findBy($criteria, $orderBy, $limit, $offset) as $object) {
            $id = $this->class->getIdentifierValue($object);

            $data[$id] = $object;
        }

        $ids = array_keys($data);
        if (empty($ids)) {
            return null;
        }

        $all = array();

        foreach ($data as $referenceId => $object) {
            $result = array();
            $result[$manager_id] = $object;

            $collections = $this->getManagersPerCollection($result[$manager_id]);

            if (empty($collections)) {
                foreach ($this->buildAndSortIdPerManager($referenceId, $models) as $manager => $id) {
                    $result[$manager] = $pool->getManager($manager)->getRepository($models[$manager])->find($id);
                }
            } else {
                foreach ($this->buildAndSortIdPerManager($referenceId, $models, $collections) as $manager => $ids) {
                    $data = $this->relayLoadModels($manager, $models[$manager], $ids);

                    $result[$manager] = $data[$referenceId];

                    foreach ($collections as $field => $coll) {
                        if ($this->class->isSingleValuedAssociation($field)) {
                            $subresult = array();
                            foreach ($coll as $id => $managers) {
                                foreach ($managers as $manager) {
                                    $subresult[$manager] = $data[$id];
                                }
                            }

                            $result[$field] = $this->createModel($this->manager->getClassMetadata($this->class->getAssociationTargetClass($field))->getName(), $subresult);
                        }
                        else {
                            $result[$field] = new ArrayCollection();

                            foreach ($coll as $id => $managers) {
                                $subresult = array();

                                foreach ($managers as $manager) {
                                    $subresult[$manager] = $data[$id];
                                }

                                $result[$field]->add($this->createModel($this->manager->getClassMetadata($this->class->getAssociationTargetClass($field))->getName(), $subresult));
                            }
                        }
                    }
                }
            }

            $all[] = $this->createModel($this->class->getName(), $result);
        }

        return $all;
    }

    /**
     * Returns list of collection.
     *
     * @param object $referenceModel
     *
     * @return array
     */
    protected function getManagersPerCollection($referenceModel)
    {
        $collections = array();
        foreach ($this->class->getAssociationReferenceNames() as $field) {
            /** @var ArrayCollection $coll */
            $coll = $referenceModel->{'get'.ucfirst($field)}();

            if ($coll->isEmpty()) {
                continue;
            }

            foreach ($coll as $cc) {
                $class = $this->manager->getClassMetadata(get_class($cc));
                $id    = $class->getIdentifierValue($cc);

                $collections[$field][$id] = $class->getFieldManagerNames();
            }
        }

        return $collections;
    }

    /**
     * @param $id
     * @param array $models
     * @param array $collections
     * @return array
     */
    protected function buildAndSortIdPerManager($id, array $models, array $collections = array())
    {
        $result = array();

        if (is_array($id)) {
            foreach ($models as $manager => $model) {
                $result[$manager] = $id;
            }
        } else {
            foreach ($models as $manager => $model) {
                $result[$manager][] = $id;
            }
        }

        foreach ($collections as $coll) {
            foreach ($coll as $id => $managers) {
                foreach ($managers as $manager) {
                    $result[$manager][] = $id;
                }
            }
        }

        return $result;
    }

    /**
     * @param string $manager
     * @param string $model
     * @param array  $ids
     * @param array  $data
     *
     * @return array
     */
    protected function relayLoadModels($manager, $model, array $ids, array $data = array())
    {
        $pool       = $this->manager->getPool();
        $methodFind = $this->class->getFieldMapping($manager)->getRepositoryMethod();

        if ($methodFind) {
            foreach ($pool->getManager($manager)->getRepository($model)->$methodFind($ids) as $object) {
                $id = $this->manager->getClassMetadata($object)->getIdentifierValue($object);

                $data[$id][$manager] = $object;
            }
        } else {
            trigger_error(sprintf('findOneBy in ModelPersister::loadAll context is depreciate. Define repository-method for "%s" manager model, see mapping for "%s".', $manager, $this->class->getName()), E_USER_DEPRECATED);

            foreach ($ids as $id) {
                $object = $pool->getManager($manager)->getRepository($model)->findOneBy(array($this->class->getFieldIdentifier() => $id));

                $id = $this->manager->getClassMetadata($object)->getIdentifierValue($object);

                $data[$id][$manager] = $object;
            }
        }

        return $data;
    }

    /**
     * @param array $models
     * @param array $ids
     * @param array $data   (optional)
     *
     * @return array
     */
    protected function generateModelsByIds(array $models, array $ids, array $data = array())
    {
        foreach ($models as $manager => $model) {
            $data = array_merge($data, $this->relayLoadModels($manager, $model, $ids));
        }

        $result = array();
        foreach ($ids as $id) {
            $result[] = $this->createModel($this->class->getName(), $data[$id]);
        }

        return $result;
    }

    /**
     * @param string $className
     * @param array  $data
     *
     * @return object
     */
    protected function createModel($className, array $data)
    {
        if (empty($data)) {
            return null;
        }

        return $this->uow->createModel($className, $data);
    }
}
