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
        $result = $this->getResult($class->getName(), array($class->getIdentifierValue($referenceModel)), $class->getFieldManagerNames(), $originManager);
        $result = current($result[$class->getName()]);

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

        $result = $this->getResult($class->getName(), $originIds, $class->getFieldManagerNames(), $originManager);

        $models = array();
        foreach ($result as $id => $data) {
            $data[$originManager] = $referenceModels[$id];

            $models = $this->createModel($class->getName(), $data);
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
                $result[$manager][$className] = array_merge($result[$manager][$className], $collection->toArray());
            }
        }

        return $result;
    }

    /**
     * Returns list of collection.
     *
     * @param object[] $referenceModels
     */
    protected function getManagersPerCollection(array $referenceModels)
    {
        foreach ($this->assocDefinitions as $assoc) {
            foreach ($referenceModels as $referenceModel) {
                $coll = $referenceModel->{'get'.ucfirst($assoc->getField())}();

                if (null === $coll || $assoc->isMany() && $coll->isEmpty()) {
                    continue;
                }

                $this->collections->append(new CollectionCenter($assoc, $this->manager->getClassMetadata($assoc->getTargetMultiModel()), $coll));
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
        $assocs = array();
        foreach ($this->buildAndSortIdPerManager($originClassName, $ids, $originManagers) as $manager => $info) {
            foreach ($info as $className => $ids) {
                if ($className !== $originClassName) {
                    $assocs[$className] = array_merge($assocs[$className], $this->relayLoadModels($this->manager->getClassMetadata($className), $manager, $ids));
                } elseif ($ignoreOriginManager !== $manager) {
                    $result = array_merge($result, $this->relayLoadModels($this->manager->getClassMetadata($className), $manager, $ids));
                }
            }
        }

        foreach ($assocs as $className => $assocResult) {
            if ($this->collections->get($className)->isMany()) {
                $listAssoc = new ArrayCollection();

                foreach ($assocResult as $data) {
                    $listAssoc->add($this->createModel($className, $data));
                }
            } else {
                $listAssoc = $this->createModel($className, current($assocResult));
            }

            $result[$this->collections->get($className)->getField()] = $listAssoc;

            unset($assocs[$className]);
        }

        // clean
        $this->collections->clean();

        return $result;
    }
}
