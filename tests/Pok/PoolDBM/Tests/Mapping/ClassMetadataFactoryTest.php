<?php

namespace Pok\PoolDBM\Tests\Mapping;

use Pok\PoolDBM\Manager\Pool;
use Pok\PoolDBM\Mapping\ClassMetadata;
use Pok\PoolDBM\ModelManager;
use Pok\PoolDBM\Tests\Mocks\MetadataDriverMock;
use Pok\PoolDBM\Tests\Mocks\ObjectManagerMock;

class ClassMetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetMetadata()
    {
        $mockDriver = new MetadataDriverMock();
        $manager = $this->createModelManager($mockDriver);

        $classFactory = $manager->getMetadataFactory();

        $class = new ClassMetadata('TestMultiModel\\User');
        $class->addModel('entity',   'Entity\\User',   array('name'));
        $class->addModel('document', 'Document\\User', array('profileContent'));
        $class->setIdentifier('document', 'id');
        $classFactory->setMetadataFor($class->getName(), $class);

        $class = $classFactory->getMetadataFor('TestMultiModel\\User');
        $this->assertTrue($class->hasIdentifier());
        $this->assertEquals(array('name', 'profileContent'), $class->getFieldNames());
    }

    protected function createModelManager($metadataDriver)
    {
        $pool = new Pool();
        $pool->addManager('entity',   new ObjectManagerMock());
        $pool->addManager('document', new ObjectManagerMock());

        $manager = new ModelManager($pool);
        $manager->setMetadataDriverImpl($metadataDriver);

        return $manager;
    }
}
