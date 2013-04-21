<?php

namespace Pok\PoolDBM\Tests\Persisters;

use Doctrine\Common\Collections\ArrayCollection;

use Pok\PoolDBM\Persisters\ModelPersister;
use Pok\PoolDBM\Mapping\ClassMetadata;
use Pok\PoolDBM\Manager\Pool;
use Pok\PoolDBM\ModelManager;
use Pok\PoolDBM\UnitOfWork;

use Pok\PoolDBM\Tests\Mocks\ObjectManagerMock;

class ModelPersisterTest extends \PHPUnit_Framework_TestCase
{
    public function test__construct()
    {
        $metadata = new ClassMetadata(__NAMESPACE__ . '\\ModelTest');
        $metadata->addModel('entity', __NAMESPACE__ . '\\EntityTest', array());
        $metadata->addModel('document', __NAMESPACE__ . '\\DocumentTest', array(), 'findByIds');
        $metadata->setIdentifier('entity', 'id');
        $metadata->addAssociation(false, 'parent', __NAMESPACE__ . '\\ModelTest');
        $metadata->addAssociation(true, 'childrens', __NAMESPACE__ . '\\ModelTest');

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

            if ($model->getId() == 3) {
                $this->assertEquals(1, count($model->childrens));
            } else {
                $this->assertEquals(0, count($model->childrens));
            }

            if ($model->getId() == 5) {
                $this->assertInstanceOf(__NAMESPACE__ . '\\ModelTest', $model->parent);
            } else {
                $this->assertNull($model->parent);
            }
        }

        $this->assertEquals(4, EntityRepository::$count + DocumentRepository::$count);
    }
}

class ModelTest
{
    public $entity;
    public $document;

    public $parent;
    public $childrens;

    public function __construct()
    {
        $this->entity = new EntityTest();
        $this->document = new DocumentTest();

        $this->childrens = new ArrayCollection();
    }
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }
    public function setDocument($document)
    {
        $this->document = $document;
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
    public function setParent(ModelTest $model)
    {
        $this->parent = $model;
    }
    public function setChildrens(array $childrens)
    {
        foreach ($childrens as $children) {
            $this->childrens->add($children);
        }
    }
}

class EntityTest
{
    public $id;
    public $parent;
    public $childrens;

    public function __construct()
    {
        $this->childrens = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getParent()
    {
        return $this->id;
    }

    public function getChildrens()
    {
        return $this->childrens;
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

            if (5 == $id) {
                $entity->parent = 3;
            }

            $list[] = $entity;
        }

        $list[2]->childrens[] = $list[4];

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
