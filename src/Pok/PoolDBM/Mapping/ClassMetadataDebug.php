<?php

namespace Pok\PoolDBM\Mapping;

/**
 * ClassMetadataDebug.
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
class ClassMetadataDebug extends ClassMetadata
{
    /**
     * {@inheritDoc}
     */
    public function setIdentifier($manager, $field)
    {
        if (!count($this->getFieldManagerNames())) {
            throw new \RuntimeException('ClassMetadata::setIdentifier must to be call after addModel.');
        }

        parent::setIdentifier($manager, $field);
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldMapping($fieldName)
    {
        if (!isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->name, $fieldName);
        }

        return parent::getFieldMapping($fieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifierValue($model, $manager = null)
    {
        if (!is_object($model)) {
            throw new \InvalidArgumentException(sprintf('You must given an object model, you given type "%s".', gettype($model)));
        }

        if ($model instanceof $this->name) {
            throw new \RuntimeException(sprintf('Model class "%s" must to be an instance of "%s"', get_class($model), $this->name));
        }

        $field = (null !== $manager)? $this->getReferenceIdentifier($manager)->referenceField : $this->identifierField;

        $method = 'get' . ucfirst($field);
        if (!method_exists($model, $method)) {
            throw new \RuntimeException(sprintf('You model "%s" must be implement method "%s".', get_class($model), $method));
        }

        return parent::getIdentifierValue($model, $manager);
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociationTargetClass($assocName)
    {
        if (!isset($this->associationMappings[$assocName])) {
            throw new \InvalidArgumentException(sprintf('Association name expected, "%s" is not an association.', $assocName));
        }

        return parent::getAssociationTargetClass($assocName);
    }

    /**
     * {@inheritdoc}
     */
    public function isSingleValuedAssociation($fieldName)
    {
        if (!isset($this->associationMappings[$fieldName])) {
            throw new \InvalidArgumentException(sprintf('Association name expected, "%s" is not an association.', $fieldName));
        }

        return parent::isSingleValuedAssociation($fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function isCollectionValuedAssociation($fieldName)
    {
        if (!isset($this->associationMappings[$fieldName])) {
            throw new \InvalidArgumentException(sprintf('Association name expected, "%s" is not an association.', $fieldName));
        }

        return parent::isCollectionValuedAssociation($fieldName);
    }
}
