<?php

namespace Pok\PoolDBM\Mapping;

class ClassMetadataFactoryDebug extends ClassMetadataFactory
{
    /**
     * {@inheritDoc}
     */
    protected function doLoadMetadata($class, $parent, $rootEntityFound, array $nonSuperclassParents)
    {
        if (!$class instanceof ClassMetadata) {
            throw new \InvalidArgumentException(sprintf('Class must by instance of Pok\\PoolDBM\\Mapping\\ClassMetadata, ', gettype($class)));
        }

        // Invoke driver
        $this->driver->loadMetadataForClass($class->getName(), $class);

        $this->validateIdentifier($class);
    }

    /**
     * {@inheritDoc}
     */
    protected function newClassMetadataInstance($className)
    {
        return new ClassMetadataDebug($className);
    }

    /**
     * Validates the identifier mapping.
     *
     * @param ClassMetadata $class
     *
     * @throws MappingException When mapping does not have identifier
     */
    protected function validateIdentifier(ClassMetadata $class)
    {
        if (!$class->hasIdentifier()) {
            throw MappingException::identifierRequired($class->getName());
        }
    }
}
