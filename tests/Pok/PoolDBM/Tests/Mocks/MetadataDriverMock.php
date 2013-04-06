<?php

namespace Pok\PoolDBM\Tests\Mocks;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;

class MetadataDriverMock extends MappingDriverChain
{
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
        return array();
    }
}