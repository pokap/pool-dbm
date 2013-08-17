<?php

namespace Pok\PoolDBM\Tests\Mocks;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

class ClassMetadataMock extends stdClassMock implements ClassMetadata
{
    public $className;
    public $identifier = array('id');

    public function getName()
    {
        return $this->className;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getReflectionClass() {}
    public function isIdentifier($fieldName) {}
    public function hasField($fieldName) {}
    public function hasAssociation($fieldName) {}
    public function isSingleValuedAssociation($fieldName) {}
    public function isCollectionValuedAssociation($fieldName) {}
    public function getFieldNames() {}
    public function getIdentifierFieldNames() {}
    public function getAssociationNames() {}
    public function getTypeOfField($fieldName) {}
    public function getAssociationTargetClass($assocName) {}
    public function isAssociationInverseSide($assocName) {}
    public function getAssociationMappedByTargetField($assocName) {}
    public function getIdentifierValues($object) {}
}
