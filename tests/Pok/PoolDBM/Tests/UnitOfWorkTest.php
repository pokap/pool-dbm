<?php

namespace Pok\PoolDBM\Tests;

use Pok\PoolDBM\UnitOfWork;

class UnitOfWorkTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateModel()
    {
        $metadata = new \Pok\PoolDBM\Mapping\ClassMetadata(__NAMESPACE__ . '\\FakeModelTest2');
        $metadata->addModel('fake', __NAMESPACE__ . '\\FakeTest', array());

        $manager = $this->getMockBuilder('Pok\\PoolDBM\\ModelManager')->disableOriginalConstructor()->getMock();
        $manager->expects($this->any())->method('getClassMetadata')->will($this->returnValue($metadata));

        $unit = new UnitOfWork($manager);

        $model = $unit->createModel(__NAMESPACE__ . '\\FakeModelTest2', array('fake' => new FakeTest));

        $this->assertInstanceOf(__NAMESPACE__ . '\\FakeModelTest2', $model);
        $this->assertInstanceOf(__NAMESPACE__ . '\\FakeTest', $model->getFake());

        $this->assertInstanceOf('Pok\\PoolDBM\\Persisters\\ModelPersister', $unit->getModelPersister(__NAMESPACE__ . '\\FakeModelTest2'));
    }
}

class FakeModelTest2 {
    private $fake;

    public function setFake($fake) {
        $this->fake = $fake;
    }

    public function getFake() {
        return $this->fake;
    }
}

class FakeTest {}
