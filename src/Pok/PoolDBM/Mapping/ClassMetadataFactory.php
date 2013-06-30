<?php

namespace Pok\PoolDBM\Mapping;

use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ReflectionService;

use Pok\PoolDBM\Mapping\ClassMetadata;
use Pok\PoolDBM\ModelManager;

class ClassMetadataFactory extends AbstractClassMetadataFactory
{
    /**
     * @var string
     */
    protected $cacheSalt = "\$POKPOOLDBMCLASSMETADATA";

    /**
     * @var ModelManager
     */
    protected $manager;

    /**
     * The used metadata driver.
     *
     * @var \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
     */
    protected $driver;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * Sets model manager.
     *
     * @param ModelManager $manager
     */
    public function setManager(ModelManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Returns model manager.
     *
     * @return ModelManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * Lazy initialization of this stuff, especially the metadata driver,
     * since these are not needed at all when a metadata cache is active.
     */
    protected function initialize()
    {
        $this->driver = $this->manager->getMetadataDriverImpl();
        $this->initialized = true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName)
    {
        return $this->namespace . '\\' . $simpleClassName;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDriver()
    {
        return $this->driver;
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadMetadata($class, $parent, $rootEntityFound)
    {
        $this->driver->loadMetadataForClass($class->getName(), $class);
    }

    /**
     * @param string $className
     *
     * @return ClassMetadata
     */
    protected function newClassMetadataInstance($className)
    {
        return new ClassMetadata($className);
    }

    /**
     * {@inheritdoc}
     */
    protected function wakeupReflection(ClassMetadataInterface $class, ReflectionService $reflService)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeReflection(ClassMetadataInterface $class, ReflectionService $reflService)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function isEntity(ClassMetadataInterface $class)
    {
        return false;
    }
}
