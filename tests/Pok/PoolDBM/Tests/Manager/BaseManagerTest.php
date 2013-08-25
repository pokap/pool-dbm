<?php

namespace Pok\PoolDBM\Tests\Manager;

use Pok\PoolDBM\Tests\Mocks\ObjectManagerMock;
use Pok\PoolDBM\ModelManager;
use Pok\PoolDBM\Manager\BaseManager;
use Pok\PoolDBM\Manager\Pool;
use Pok\PoolDBM\Mapping\ClassMetadata;

class BaseManagerTest extends \PHPUnit_Framework_TestCase
{
    public function test__construct()
    {
        $metadata = new ClassMetadata(__NAMESPACE__ . '\\ModelTest');
        $metadata->addModel('entity', __NAMESPACE__ . '\\EntityTest');
        $metadata->setIdentifier('entity', 'id');

        $metadataFactory = $this->getMock('Pok\\PoolDBM\\Mapping\\ClassMetadataFactory', array('getMetadataFor', 'setModelManager'));
        $metadataFactory->expects($this->any())->method('getMetadataFor')->will($this->returnValue($metadata));

        $refl = new \ReflectionClass('Pok\\PoolDBM\\Manager\\BaseManager');
        $this->assertTrue($refl->isInstantiable());

        $pool = new Pool();
        $pool->addManager('entity', new EntityManager());

        $manager = new TestManager(__NAMESPACE__ . '\\ModelTest', new ModelManager($pool, $metadataFactory));
        $refl = new \ReflectionClass(get_class($manager));

        $repo = $refl->getMethod('getRepository');
        $this->assertTrue($repo->isProtected());
        $repo->setAccessible(true);
        $this->assertInstanceOf('Pok\\PoolDBM\\ModelRepository', $repo->invoke($manager));

        $this->assertInstanceOf(__NAMESPACE__ . '\\ModelTest', $manager->create());

        $manager->save(new ModelTest());
        $manager->save(new ModelTest(), true);

        try {
            $manager->save(new \stdClass());
        } catch (\RuntimeException $e) {
            $this->assertEquals('Manager "Pok\PoolDBM\Tests\Manager\TestManager" is unable to save model "stdClass"',$e->getMessage());
        }

        $manager->clear();

        $this->assertInstanceOf(__NAMESPACE__ . '\\ModelTest', $manager->find(null));
        $this->assertEquals(1, count($manager->findBy(array())));
        $this->assertInstanceOf(__NAMESPACE__ . '\\ModelTest', $manager->findOneBy(array()));
        $this->assertEquals(1, count($manager->findAll()));
    }
}

class ModelTest
{
    protected $entity;

    public function __construct()
    {
        $this->entity = new EntityTest();
    }

    public function getId()
    {
        return $this->entity->getId();
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
    }
}

class EntityTest
{
    public function setId($id)
    {
    }

    public function getId()
    {
        return 1;
    }
}

class EntityManager extends ObjectManagerMock
{
    public function getRepository($entityClass)
    {
        return new EntityRepository();
    }

    public function persist($entity)
    {
        if (!$entity instanceof EntityTest) {
            throw new \RuntimeException();
        }
    }

    public function remove($entity)
    {
        if (!$entity instanceof EntityTest) {
            throw new \RuntimeException();
        }
    }

    public function clear($entity = null)
    {
        if (null !== $entity) {
            throw new \RuntimeException();
        }
    }
}

class EntityRepository
{
    public function find($id)
    {
        return new EntityTest();
    }

    public function findBy(array $criteria, array $order = null, $limit = null, $offset = null)
    {
        return array(new EntityTest());
    }

    public function findOneBy(array $criteria)
    {
        return new EntityTest();
    }

    public function findAll()
    {
        return array(new EntityTest());
    }
}

class TestManager extends BaseManager {}
