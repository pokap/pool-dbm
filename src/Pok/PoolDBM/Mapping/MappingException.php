<?php

namespace Pok\PoolDBM\Mapping;

class MappingException extends \RuntimeException
{
    public static function mappingNotFound($className, $field)
    {
        return new self(sprintf('No mapping found for field "%s" in class "%s".', $field, $className));
    }

    public static function duplicateFieldMapping($model, $field)
    {
        return new self(sprintf('Property "%s" in "%s" was already declared, but it must be declared only once', $field, $model));
    }

    public static function identifierRequired($className)
    {
        return new self(sprintf('Identifier in mapping "%s" must have been required.', $className));
    }
}
