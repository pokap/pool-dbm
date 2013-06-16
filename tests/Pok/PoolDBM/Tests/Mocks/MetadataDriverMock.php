<?php

namespace Pok\PoolDBM\Tests\Mocks;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;

class MetadataDriverMock extends MappingDriverChain
{
    protected $classNames;

    public function __construct(array $classNames = array())
    {
        $this->classNames = $classNames;
    }

    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        return;
    }

    public function isTransient($className)
    {
        return false;
    }

    public function getAllClassNames()
    {
        return $this->classNames;
    }
}
