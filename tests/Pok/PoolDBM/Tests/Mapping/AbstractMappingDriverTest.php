<?php

namespace Pok\PoolDBM\Tests\Mapping;

use Pok\PoolDBM\Mapping\ClassMetadata;

require_once __DIR__ . '/Driver/fixtures/User.php';

abstract class AbstractMappingDriverTest extends \PHPUnit_Framework_TestCase
{
    abstract protected function _loadDriver();

    public function testLoadMapping()
    {
        $className = 'TestMultiModel\User';
        $mappingDriver = $this->_loadDriver();

        $class = new ClassMetadata($className);
        $mappingDriver->loadMetadataForClass($className, $class);

        return $class;
    }

    /**
     * @depends testLoadMapping
     * @param ClassMetadata $class
     */
    public function testFieldMappings($class)
    {
        $this->assertEquals(2, count($class->getFieldManagerNames()));

        $this->assertTrue($class->hasFieldMapping('entity'));
        $this->assertTrue($class->hasFieldMapping('document'));

        $this->assertEquals(array('name'), $class->getFieldMapping('entity')->getFields());
        $this->assertEquals(array('profileContent'), $class->getFieldMapping('document')->getFields());

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testIdentifier($class)
    {
        $this->assertEquals('id', $class->getFieldIdentifier());
        $this->assertEquals('document', $class->getManagerIdentifier());

        return $class;
    }
}
