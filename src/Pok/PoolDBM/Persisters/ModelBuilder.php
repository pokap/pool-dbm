<?php

namespace Pok\PoolDBM\Persisters;

use Doctrine\Common\Collections\ArrayCollection;
use Pok\PoolDBM\Mapping\ClassMetadata;
use Pok\PoolDBM\Mapping\Definition\AssociationDefinition;
use Pok\PoolDBM\ModelManager;
use Pok\PoolDBM\UnitOfWork;

class ModelBuilder
{
    /**
     * @var ModelManager
     */
    protected $manager;

    /**
     * @var AssociationDefinition[]
     */
    protected $assocDefinitions;

    /**
     * @var CollectionCenterIterator
     */
    protected $collections;

    /**
     * Constructor.
     *
     * @param ModelManager  $manager
     * @param UnitOfWork    $uow
     * @param ClassMetadata $class
     */
    public function __construct(ModelManager $manager, UnitOfWork $uow, ClassMetadata $class)
    {
        $this->manager = $manager;
        $this->uow     = $uow;
        $this->class   = $class;

        $this->collections = new CollectionCenterIterator();
    }

    /**
     * @param mixed  $referenceModel
     * @param string $originManager
     *
     * @return mixed
     */
    public function build($referenceModel, $originManager)
    {
        $this->getManagersPerCollection(array($referenceModel));
        $result = $this->getResult(array($referenceModel), $originManager);

        if (!empty($result)) {
            $result = reset($result);
        }

        $result[$originManager] = $referenceModel;

        return $this->createModel($this->class->getName(), $result);
    }

    /**
     * @param array  $referenceModels
     * @param string $originManager
     *
     * @return array
     */
    public function buildAll(array $referenceModels, $originManager)
    {
        $this->getManagersPerCollection($referenceModels);
        $result = $this->getResult($referenceModels, $originManager);

        // pre-init data
        foreach ($referenceModels as $referenceModel) {
            $result[$this->class->getIdentifierValue($referenceModel)][$originManager] = $referenceModel;
        }

        $models = array();
        foreach ($result as $data) {
            $models[] = $this->createModel($this->class->getName(), $data);
        }

        return $models;
    }

    /**
     * @param string $className
     * @param array  $data
     *
     * @return mixed
     */
    public function createModel($className, array $data)
    {
        if (empty($data)) {
            return null;
        }

        return $this->uow->createModel($className, $data);
    }

    /**
     * Performed research on the model via their repository.
     *
     * @param ClassMetadata $class
     * @param string        $manager
     * @param array         $ids
     * @param callable      $stacker
     */
    public function loaderModels(ClassMetadata $class, $manager, array $ids, \Closure $stacker)
    {
        $classOfManagerName = $class->getFieldMapping($manager);

        $pool       = $this->manager->getPool();
        $methodFind = $classOfManagerName->getRepositoryMethod();

        $repository = $pool->getManager($manager)->getRepository($classOfManagerName->getName());

        if ($methodFind && method_exists($repository, $methodFind)) {
            foreach ($repository->$methodFind($ids) as $object) {
                $id = $class->getIdentifierValue($object);

                $stacker($id, $object);
            }
        } else {
            trigger_error(sprintf('findOneBy in ModelPersister::loadAll context is depreciate. Define repository-method for "%s" manager model, see mapping for "%s".', $manager, $class->getName()), E_USER_DEPRECATED);

            $repository = $pool->getManager($manager)->getRepository($classOfManagerName->getName());
            $field      = $class->getIdentifierReference($manager)->field;

            foreach ($ids as $id) {
                $object = $repository->findOneBy(array($field => $id));

                if (!$object) {
                    continue;
                }

                $id = $class->getIdentifierValue($object, $manager);

                $stacker($id, $object);
            }
        }
    }

    /**
     * @param array $referenceModels
     * @param array $managers
     *
     * @return array
     */
    protected function buildAndSortIdPerManager(array $referenceModels, array $managers)
    {
        $result = array();

        foreach ($referenceModels as $referenceModel) {
            foreach ($managers as $manager) {
                $result[$manager][$this->class->getName()][] = $this->class->getIdentifierValue($referenceModel, $manager);
            }
        }

        foreach ($this->collections as $className => $collection) {
            /** @var CollectionCenter $collection */
            foreach ($collection->getManagers() as $manager) {
                if (!isset($result[$manager][$className])) {
                    $result[$manager][$className] = array();
                }

                $result[$manager][$className] = array_unique(array_merge($result[$manager][$className], $collection->toArray()));
            }
        }

        return $result;
    }

    /**
     * Returns list of collection.
     *
     * @param array $referenceModels
     */
    protected function getManagersPerCollection(array $referenceModels)
    {
        $this->collections->clean();

        foreach ($this->class->getAssociationDefinitions() as $assoc) {
            $compatible = $assoc->getCompatible();

            if (!empty($compatible) && !in_array($this->class->getManagerIdentifier(), $compatible)) {
                continue;
            }

            foreach ($referenceModels as $referenceModel) {
                $method = 'get' . ucfirst($assoc->getReferenceField($this->class->getManagerIdentifier()));

                /** @var null|object|ArrayCollection $coll */
                $coll = call_user_func(array($referenceModel, $method));

                if (null === $coll || $assoc->isMany() && $coll->isEmpty()) {
                    continue;
                }

                $this->collections->append(new CollectionCenter($assoc, $this->manager->getClassMetadata($assoc->getTargetMultiModel()), $coll, $this->class->getIdentifierValue($referenceModel)));
            }
        }
    }

    /**
     * @param ClassMetadata $class
     * @param string        $manager
     * @param array         $ids
     *
     * @return array
     */
    protected function relayLoadModels(ClassMetadata $class, $manager, array $ids)
    {
        $data    = array();
        $stacker = function ($id, $object) use (&$data) {
            $data[$id] = $object;
        };

        $this->loaderModels($class, $manager, $ids, $stacker);

        return $data;
    }

    /**
     * @param array  $ids
     * @param string $ignoreOriginManager
     *
     * @return array
     */
    protected function getResult(array $referenceModels, $ignoreOriginManager)
    {
        $originManagers = $this->class->getFieldManagerNames();

        $result = array();
        $resultByClass = array();
        $datas  = array();
        $assocs = array();

        // pool data by id & manager
        foreach ($this->buildAndSortIdPerManager($referenceModels, array_diff($originManagers, array($ignoreOriginManager))) as $manager => $info) {
            foreach ($info as $className => $ids) {
                foreach ($this->relayLoadModels($this->manager->getClassMetadata($className), $manager, $ids) as $id => $data) {
                    $datas[$className][$id][$manager] = $data;

                    $resultByClass[$className][] = $id;
                }
            }
        }

        if (isset($datas[$this->class->getName()])) {
            foreach ($referenceModels as $referenceModel) {
                foreach ($originManagers as $manager) {
                    $id        = $this->class->getIdentifierValue($referenceModel);
                    $managerId = $this->class->getIdentifierValue($referenceModel, $manager);

                    if (isset($datas[$this->class->getName()][$managerId][$manager])) {
                        $result[$id][$manager] = $datas[$this->class->getName()][$managerId][$manager];
                    }
                }
            }
        }

        if (!$this->collections->isEmpty()) {
            foreach ($this->collections as $collection) {
                /** @var CollectionCenter $collection */
                if (!isset($resultByClass[$collection->getClassName()])) {
                    continue;
                }

                foreach ($collection->toArray() as $id) {
                    if (!in_array($id, $resultByClass[$collection->getClassName()])) {
                        continue;
                    }

                    $class = $this->manager->getClassMetadata($collection->getClassName());

                    if (!isset($datas[$collection->getClassName()][$id][$class->getManagerIdentifier()])) {
                        continue;
                    }

                    foreach ($collection->getManagers() as $manager) {
                        if (!isset($datas[$collection->getClassName()][$id][$manager])) {
                            continue;
                        }

                        if (!isset($assocs[$collection->getIdentifierRef()][$id])) {
                            $assocs[$collection->getIdentifierRef()][$id] = new CollectionCenterData($collection);
                        }

                        $assocs[$collection->getIdentifierRef()][$id]->addData($manager, $datas[$collection->getClassName()][$id][$manager]);
                    }
                }
            }

            foreach ($assocs as $idRef => $collDatas) {
                /** @var CollectionCenterData[] $collDatas */
                foreach ($collDatas as $collData) {
                    $collection = $collData->getCollectionCenter();

                    if ($collection->isMany()) {
                        $result[$idRef][$collection->getField()][] = $this->createModel(
                            $collection->getClassName(),
                            $collData->getDatas()
                        );
                    } else {
                        $result[$idRef][$collection->getField()] = $this->createModel(
                            $collection->getClassName(),
                            $collData->getFirstData()
                        );
                    }
                }
            }

            // clean
            $this->collections->clean();
        }

        return $result;
    }
}
