<?php

namespace Pok\PoolDBM\Tests\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;

use Pok\PoolDBM\Mapping\ClassMetadata;

require_once __DIR__ . '/fixtures/User.php';

abstract class AbstractDriverTest extends \PHPUnit_Framework_TestCase
{
    /** @var FileDriver */
    protected $driver;

    public function tearDown()
    {
        unset($this->driver);
    }

    public function testDriver()
    {
        $classMetadata = new ClassMetadata('TestMultiModel\User');
        $this->driver->loadMetadataForClass('TestMultiModel\User', $classMetadata);

        $this->assertTrue($classMetadata->hasIdentifier());
        $this->assertEquals('document', $classMetadata->getManagerIdentifier());
        $this->assertEquals('id', $classMetadata->getFieldIdentifier());

        $this->assertEquals(array('name', 'profileContent'), $classMetadata->getFieldNames());
        $this->assertEquals(array('entity', 'document'), $classMetadata->getFieldManagerNames());
    }
}
