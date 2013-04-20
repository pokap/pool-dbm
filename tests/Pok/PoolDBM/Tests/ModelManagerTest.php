<?php

namespace Pok\PoolDBM\Tests;

use Pok\PoolDBM\ModelManager;
use Pok\PoolDBM\Manager\Pool;
use Pok\PoolDBM\Tests\Mocks\ObjectManagerMock;

class ModelManagerTest extends \PHPUnit_Framework_TestCase
{
    public function test__construct()
    {
        $metadata = new \Pok\PoolDBM\Mapping\ClassMetadata(__NAMESPACE__ . '\\ModelTest');
        $metadata->addModel('entity', __NAMESPACE__ . '\\EntityTest', array());
        $metadata->setIdentifier('entity', 'id');

        $metadataFactory = $this->getMock('Pok\\PoolDBM\\Mapping\\ClassMetadataFactory', array('getMetadataFor', 'setModelManager'));
        $metadataFactory->expects($this->any())->method('getMetadataFor')->will($this->returnValue($metadata));

        $pool = new Pool();
        $pool->addManager('entity', new EntityManager());

        $manager = new ModelManager($pool, $metadataFactory);

        $this->assertInstanceOf('Pok\\PoolDBM\\Mapping\\ClassMetadataFactory', $manager->getMetadataFactory());
        $this->assertTrue($manager->getPool()->hasManager('entity'));
        $this->assertInstanceOf(get_class($metadata), $manager->getClassMetadata(__NAMESPACE__ . '\\ModelTest'));

        $this->assertTrue($manager->createQueryBuilder(__NAMESPACE__ . '\\ModelTest', 'test'));

        $this->assertInstanceOf('Pok\\PoolDBM\\ModelRepository', $manager->getRepository(__NAMESPACE__ . '\\ModelTest'));

        $this->assertTrue($manager->contains(new ModelTest));

        // unitOfWork
        $this->assertInstanceOf('Pok\\PoolDBM\\UnitOfWork', $manager->getUnitOfWork());

        $manager->persist(new ModelTest);
        $manager->remove(new ModelTest);
        $manager->refresh(new ModelTest);
        $manager->detach(new ModelTest);
        $manager->merge(new ModelTest);
        $manager->flush(new ModelTest);

        $manager->close();

        try {
            $manager->flush(new ModelTest);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Model manager is closed.', $e->getMessage());
        }
    }

    public function testCustomRepository()
    {
        $metadata = new \Pok\PoolDBM\Mapping\ClassMetadata(__NAMESPACE__ . '\\ModelTest');
        $metadata->setCustomRepositoryClass(__NAMESPACE__ . '\\ModelRepository');

        $metadataFactory = $this->getMock('Pok\\PoolDBM\\Mapping\\ClassMetadataFactory', array('getMetadataFor', 'setModelManager'));
        $metadataFactory
            ->expects($this->any())
            ->method('getMetadataFor')
            ->will($this->returnValue($metadata));

        $manager = new ModelManager(new Pool(), $metadataFactory);
        $this->assertInstanceOf(__NAMESPACE__ . '\\ModelRepository', $manager->getRepository(__NAMESPACE__ . '\\ModelTest'));

        $this->assertTrue($manager->find(__NAMESPACE__ . '\\ModelTest', null));
    }
}

class ModelTest
{
    public function getEntity()
    {
        return '$ENTITYCLASS';
    }
}

class EntityTest
{
    private $id;
}

class EntityManager extends ObjectManagerMock
{
    public function getRepository($entityClass)
    {
        return new EntityRepository();
    }

    public function persist($entity)
    {
        if ('$ENTITYCLASS' != $entity) {
            throw new \RuntimeException();
        }
    }

    public function remove($entity)
    {
        if ('$ENTITYCLASS' != $entity) {
            throw new \RuntimeException();
        }
    }

    public function refresh($entity)
    {
        if ('$ENTITYCLASS' != $entity) {
            throw new \RuntimeException();
        }
    }

    public function detach($entity)
    {
        if ('$ENTITYCLASS' != $entity) {
            throw new \RuntimeException();
        }
    }

    public function merge($entity)
    {
        if ('$ENTITYCLASS' != $entity) {
            throw new \RuntimeException();
        }
    }

    public function flush($entity = null)
    {
        if ('$ENTITYCLASS' != $entity) {
            throw new \RuntimeException();
        }
    }

    public function clear($entity = null)
    {
        if (null != $entity) {
            throw new \RuntimeException();
        }
    }

    public function contains($object)
    {
        return true;
    }
}

class ModelRepository extends \Pok\PoolDBM\ModelRepository
{
    public function find($id)
    {
        return true;
    }
}

class EntityRepository
{
    public function createQueryBuilder($alias)
    {
        return true;
    }
}
