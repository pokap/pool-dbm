<?php

namespace Pok\PoolDBM\Tests\Persisters;

use Pok\PoolDBM\Persisters\ModelPersister;
use Pok\PoolDBM\Mapping\ClassMetadata;
use Pok\PoolDBM\Manager\Pool;
use Pok\PoolDBM\ModelManager;
use Pok\PoolDBM\Tests\Mocks\ObjectManagerMock;
use Pok\PoolDBM\UnitOfWork;

class ModelPersisterTest extends \PHPUnit_Framework_TestCase
{
    public function test__construct()
    {
        $metadata = new ClassMetadata(__NAMESPACE__ . '\\ModelTest');
        $metadata->addModel('entity', __NAMESPACE__ . '\\EntityTest', array());
        $metadata->addModel('document', __NAMESPACE__ . '\\DocumentTest', array(), 'findByIds');
        $metadata->setIdentifier('entity', 'id');

        $metadataFactory = $this->getMock('Pok\\PoolDBM\\Mapping\\ClassMetadataFactory', array('getMetadataFor', 'setModelManager'));
        $metadataFactory->expects($this->any())->method('getMetadataFor')->will($this->returnValue($metadata));

        $pool = new Pool();
        $pool->addManager('entity',   new EntityManager());
        $pool->addManager('document', new DocumentManager());

        $manager = new ModelManager($pool, $metadataFactory);
        $persisters = new ModelPersister($manager, new UnitOfWork($manager), $metadata);

        $this->assertInstanceOf('Pok\\PoolDBM\\Mapping\\ClassMetadata', $persisters->getClassMetadata());

        $model = $persisters->load(1);
        $this->assertInstanceOf(__NAMESPACE__ . '\\ModelTest', $model);
        $this->assertInstanceOf(__NAMESPACE__ . '\\EntityTest', $model->entity);
        $this->assertEquals(1, $model->entity->id);
        $this->assertInstanceOf(__NAMESPACE__ . '\\DocumentTest', $model->document);
        $this->assertEquals(1, $model->document->id);

        $models = $persisters->loadAll();
        foreach ($models as $model) {
            $this->assertInstanceOf(__NAMESPACE__ . '\\ModelTest', $model);

            $this->assertInstanceOf(__NAMESPACE__ . '\\EntityTest', $model->entity);
            $this->assertInstanceOf(__NAMESPACE__ . '\\DocumentTest', $model->document);
            $this->assertEquals($model->entity->id, $model->document->id);
        }

        $this->assertEquals(4, EntityRepository::$count + DocumentRepository::$count);
    }
}

class ModelTest
{
    public $entity;
    public $document;
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }
    public function getEntity()
    {
        return $this->entity;
    }
    public function setDocument($document)
    {
        $this->document = $document;
    }
    public function getDocument()
    {
        return $this->document;
    }
    public function getId()
    {
        return $this->entity->id;
    }
    public function setId($id)
    {
        $this->entity->id = $id;
        $this->document->id = $id;
    }
}

class EntityTest
{
    public $id;
    public function getId()
    {
        return $this->id;
    }
}

class DocumentTest
{
    public $id;
    public function getId()
    {
        return $this->id;
    }
}

class EntityManager extends ObjectManagerMock
{
    public function getRepository($entityClass)
    {
        return new EntityRepository();
    }
}

class DocumentManager extends ObjectManagerMock
{
    public function getRepository($documentClass)
    {
        return new DocumentRepository();
    }
}

class EntityRepository
{
    public static $count = 0;

    public function findOneBy($criteria)
    {
        self::$count++;

        $entity = new EntityTest;
        $entity->id = $criteria['id'];

        return $entity;
    }

    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        self::$count++;

        $list = array();
        foreach (range(1, 5) as $id) {
            $entity = new EntityTest;
            $entity->id = $id;

            $list[] = $entity;
        }

        return $list;
    }
}

class DocumentRepository
{
    public static $count = 0;

    public function find($id)
    {
        self::$count++;

        $document = new DocumentTest;
        $document->id = $id;

        return $document;
    }

    public function findByIds(array $ids)
    {
        self::$count++;

        $list = array();
        foreach ($ids as $id) {
            $document = new DocumentTest;
            $document->id = $id;

            $list[] = $document;
        }

        return $list;
    }
}
