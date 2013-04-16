<?php

namespace Pok\PoolDBM\Persisters;

use Doctrine\Common\Collections\ArrayCollection;
use Pok\PoolDBM\Mapping\ClassMetadata;
use Pok\PoolDBM\Mapping\Definition\AssociationDefinition;
use Pok\PoolDBM\ModelManager;
use Pok\PoolDBM\UnitOfWork;

class ModelBuilder
{
    protected $manager;
    protected $assocDefinitions;
    protected $collections;
    protected $collectionsManagers;
    protected $collectionsDefinition;

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

        $this->collections = array();
        $this->collectionsManagers = array();
        $this->collectionsDefinition = array();
    }

    /**
     * @param mixed  $originId
     * @param array  $originManagers
     * @param object $referenceModel
     * @param string $originClassName
     * @param string $originManager
     *
     * @return object
     */
    public function build($originId, array $originManagers, $referenceModel, $originClassName, $originManager)
    {
        $result = $this->getResult($originClassName, array($originId), $originManagers, $originManager);
        $result = current($result[$originClassName]);

        $result[$originManager] = $referenceModel;

        return $this->createModel($originClassName, $result);
    }

    /**
     * @param array  $originManagers
     * @param array  $referenceModels
     * @param string $originClassName
     * @param string $originManager
     *
     * @return object[]
     */
    public function buildAll(array $originManagers, array $referenceModels, $originClassName, $originManager)
    {
        $originIds = array_keys($referenceModels);
        if (empty($originIds)) {
            return array();
        }

        $result = $this->getResult($originClassName, $originIds, $originManagers, $originManager);

        $models = array();
        foreach ($result as $id => $data) {
            $data[$originManager] = $referenceModels[$id];

            $models = $this->createModel($originClassName, $data);
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

        foreach ($this->collectionsManagers as $className => $managers) {
            foreach ($managers as $manager) {
                $result[$manager][$className] = array_merge($result[$manager][$className], $this->collections[$className]);
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

                $class = $this->manager->getClassMetadata($assoc->getTargetMultiModel());
                $this->collectionsManagers[$assoc->getTargetMultiModel()]   = $class->getFieldManagerNames();
                $this->collectionsDefinition[$assoc->getTargetMultiModel()] = array($assoc->getField(), $assoc->isMany());

                if ($assoc->isMany()) {
                    foreach ($coll as $cc) {
                        $id = $class->getIdentifierValue($cc);

                        $this->collections[$class->getName()][] = $id;
                    }
                } else {
                    $id = $class->getIdentifierValue($coll);

                    $this->collections[$class->getName()] = array($id);
                }
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
                $id = $this->manager->getClassMetadata($object)->getIdentifierValue($object);

                $data[$id][$manager] = $object;
            }
        } else {
            trigger_error(sprintf('findOneBy in ModelPersister::loadAll context is depreciate. Define repository-method for "%s" manager model, see mapping for "%s".', $manager, $class->getName()), E_USER_DEPRECATED);

            foreach ($ids as $id) {
                $object = $pool->getManager($manager)->getRepository($class->getName())->findOneBy(array($class->getFieldIdentifier() => $id));

                $id = $this->manager->getClassMetadata($object)->getIdentifierValue($object);

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
            if ($this->collectionsDefinition[$className][1]) {
                $listAssoc = new ArrayCollection();

                foreach ($assocResult as $data) {
                    $listAssoc->add($this->createModel($className, $data));
                }

            } else {
                $listAssoc = $this->createModel($className, current($assocResult));
            }

            $result[$this->collectionsDefinition[$className][0]] = $listAssoc;

            unset($assocs[$className]);
        }

        // clean
        $this->collections = array();
        $this->collectionsManagers = array();
        $this->collectionsDefinition = array();

        return $result;
    }
}
