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
     * @param ModelManager            $manager
     * @param UnitOfWork              $uow
     * @param AssociationDefinition[] $assocDefinitions
     */
    public function __construct(ModelManager $manager, UnitOfWork $uow, array $assocDefinitions)
    {
        $this->manager          = $manager;
        $this->uow              = $uow;
        $this->assocDefinitions = $assocDefinitions;

        $this->collections = new CollectionCenterIterator();
    }

    /**
     * @param ClassMetadata $class
     * @param object        $referenceModel
     * @param string        $originManager
     *
     * @return object
     */
    public function build(ClassMetadata $class, $referenceModel, $originManager)
    {
        $this->getManagersPerCollection($class, array($referenceModel));
        $result = $this->getResult($class->getName(), array($class->getIdentifierValue($referenceModel)), $class->getFieldManagerNames(), $originManager);
        if (!empty($result)) {
            $result = reset($result);
        }

        $result[$originManager] = $referenceModel;

        return $this->createModel($class->getName(), $result);
    }

    /**
     * @param ClassMetadata $class
     * @param array         $referenceModels
     * @param string        $originManager
     *
     * @return object[]
     */
    public function buildAll(ClassMetadata $class, array $referenceModels, $originManager)
    {
        $originIds = array_keys($referenceModels);
        if (empty($originIds)) {
            return array();
        }

        $this->getManagersPerCollection($class, $referenceModels);
        $result = $this->getResult($class->getName(), $originIds, $class->getFieldManagerNames(), $originManager);

        // pre-init data
        foreach ($originIds as $id) {
            $result[$id][$originManager] = $referenceModels[$id];
        }

        $models = array();
        foreach ($result as $data) {
            $models[] = $this->createModel($class->getName(), $data);
        }

        return $models;
    }

    /**
     * @param string $className
     * @param array  $data
     *
     * @return object
     */
    public function createModel($className, array $data)
    {
        if (empty($data)) {
            return null;
        }

        return $this->uow->createModel($className, $data);
    }

    /**
     * @param string $className
     * @param array  $ids
     * @param array  $managers
     *
     * @return array
     */
    protected function buildAndSortIdPerManager($className, array $ids, array $managers)
    {
        $result = array();

        foreach ($managers as $manager) {
            $result[$manager][$className] = $ids;
        }

        foreach ($this->collections as $className => $collection) {
            /** @var CollectionCenter $collection */
            foreach ($collection->getManagers() as $manager) {
                if (!isset($result[$manager])) {
                    $result[$manager] = array($className => array());
                }

                $result[$manager][$className] = array_unique(array_merge($result[$manager][$className], $collection->toArray()));
            }
        }

        return $result;
    }

    /**
     * Returns list of collection.
     *
     * @param ClassMetadata $class
     * @param object[]      $referenceModels
     */
    protected function getManagersPerCollection(ClassMetadata $class, array $referenceModels)
    {
        foreach ($this->assocDefinitions as $assoc) {
            foreach ($referenceModels as $referenceModel) {
                /** @var null|object|ArrayCollection $coll */
                $coll = $referenceModel->{'get'.ucfirst($assoc->getReferenceField())}();

                if (null === $coll || $assoc->isMany() && $coll->isEmpty()) {
                    continue;
                }

                $this->collections->append(new CollectionCenter($assoc, $this->manager->getClassMetadata($assoc->getTargetMultiModel()), $coll, $class->getIdentifierValue($referenceModel)));
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
        $data       = array();
        $pool       = $this->manager->getPool();
        $methodFind = $class->getFieldMapping($manager)->getRepositoryMethod();

        if ($methodFind) {
            foreach ($pool->getManager($manager)->getRepository($class->getName())->$methodFind($ids) as $object) {
                $id = $class->getIdentifierValue($object, $manager);

                $data[$id][$manager] = $object;
            }
        } else {
            trigger_error(sprintf('findOneBy in ModelPersister::loadAll context is depreciate. Define repository-method for "%s" manager model, see mapping for "%s".', $manager, $class->getName()), E_USER_DEPRECATED);

            $repository = $pool->getManager($manager)->getRepository($class->getName());

            foreach ($ids as $id) {
                $object = $repository->findOneBy(array($class->getFieldIdentifier() => $id));

                $id = $class->getIdentifierValue($object, $manager);

                $data[$id][$manager] = $object;
            }
        }

        return $data;
    }

    /**
     * @param string $originClassName
     * @param array  $ids
     * @param array  $originManagers
     * @param string $ignoreOriginManager
     *
     * @return array
     */
    protected function getResult($originClassName, array $ids, array $originManagers, $ignoreOriginManager)
    {
        $result = array();
        $resultByClass = array();
        $datas  = array();
        $assocs = array();

        // pool data by id & manager
        foreach ($this->buildAndSortIdPerManager($originClassName, $ids, array_diff($originManagers, array($ignoreOriginManager))) as $manager => $info) {
            foreach ($info as $className => $ids) {
                foreach ($this->relayLoadModels($this->manager->getClassMetadata($className), $manager, $ids) as $id => $data) {
                    if (!isset($datas[$id])) {
                        $datas[$id] = $data;
                    } else {
                        $datas[$id] += $data;
                    }

                    $resultByClass[$className][] = $id;
                }
            }
        }

        if (isset($resultByClass[$originClassName])) {
            foreach ($resultByClass[$originClassName] as $id) {
                $datas_managers = array_keys($datas[$id]);

                foreach ($originManagers as $manager) {
                    if (!in_array($manager, $datas_managers)) {
                        continue;
                    }

                    $result[$id][$manager] = $datas[$id][$manager];
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

                    if (!isset($datas[$id][$class->getManagerIdentifier()])) {
                        continue;
                    }

                    foreach ($collection->getManagers() as $manager) {
                        if (!isset($datas[$id][$manager])) {
                            continue;
                        }

                        $assocs[$collection->getIdentifierRef()][$collection->getClassName()][$collection->getField()][$id][$manager] = $datas[$id][$manager];
                    }
                }
            }

            foreach ($assocs as $idAssoc => $info) {
                foreach ($info as $className => $fields) {
                    foreach ($fields as $field => $assocResult) {
                        $center = $this->collections->get($className, $field);

                        if ($center->isMany()) {
                            $listAssocs = array();

                            foreach ($assocResult as $data) {
                                $listAssocs[] = $this->createModel($className, $data);
                            }
                        } else {
                            $listAssocs = $this->createModel($className, current($assocResult));
                        }

                        $result[$idAssoc][$field] = $listAssocs;
                    }
                }
            }

            // clean
            $this->collections->clean();
        }

        return $result;
    }
}
