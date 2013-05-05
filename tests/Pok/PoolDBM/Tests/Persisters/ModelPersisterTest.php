<?php

namespace Pok\PoolDBM\Tests\Persisters;

use Pok\PoolDBM\Persisters\ModelPersister;
use Pok\PoolDBM\Mapping\ClassMetadataDebug as ClassMetadata;
use Pok\PoolDBM\Manager\Pool;
use Pok\PoolDBM\ModelManagerDebug as ModelManager;
use Pok\PoolDBM\UnitOfWork;

use Pok\PoolDBM\Tests\Fixtures;

class ModelPersisterTest extends \PHPUnit_Framework_TestCase
{
    public function test__construct()
    {
        $metadata = new ClassMetadata('Pok\\PoolDBM\\Tests\\Fixtures\\Test1\\MultiModel');
        $metadata->addModel('entity', 'Pok\\PoolDBM\\Tests\\Fixtures\\Test1\\Entity', array(), 'findByIds');
        $metadata->addModel('document', 'Pok\\PoolDBM\\Tests\\Fixtures\\Test1\\Document', array(), 'findByIds');
        $metadata->setIdentifier('entity', 'id');
        $metadata->addAssociation(false, 'parent', 'Pok\\PoolDBM\\Tests\\Fixtures\\Test1\\MultiModel');
        $metadata->addAssociation(true, 'childrens', 'Pok\\PoolDBM\\Tests\\Fixtures\\Test1\\MultiModel');

        $metadataFactory = $this->getMock('Pok\\PoolDBM\\Mapping\\ClassMetadataFactory', array('getMetadataFor', 'setModelManager'));
        $metadataFactory->expects($this->any())->method('getMetadataFor')->will($this->returnValue($metadata));

        $pool = new Pool();
        $pool->addManager('entity',   new Fixtures\Test1\EntityManager());
        $pool->addManager('document', new Fixtures\Test1\DocumentManager());

        $manager = new ModelManager($pool, $metadataFactory);
        $persisters = new ModelPersister($manager, new UnitOfWork($manager), $metadata);

        $this->assertInstanceOf('Pok\\PoolDBM\\Mapping\\ClassMetadata', $persisters->getClassMetadata());

        $model = $persisters->load(1);
        $this->assertInstanceOf('Pok\\PoolDBM\\Tests\\Fixtures\\Test1\\MultiModel', $model);
        $this->assertInstanceOf('Pok\\PoolDBM\\Tests\\Fixtures\\Test1\\Entity', $model->entity);
        $this->assertEquals(1, $model->entity->id);
        $this->assertInstanceOf('Pok\\PoolDBM\\Tests\\Fixtures\\Test1\\Document', $model->document);
        $this->assertEquals(1, $model->document->id);

        $models = $persisters->loadAll();

        $this->assertEquals(5, count($models));

        foreach ($models as $model) {
            $this->assertInstanceOf('Pok\\PoolDBM\\Tests\\Fixtures\\Test1\\MultiModel', $model);

            $this->assertInstanceOf('Pok\\PoolDBM\\Tests\\Fixtures\\Test1\\Entity', $model->entity);
            $this->assertInstanceOf('Pok\\PoolDBM\\Tests\\Fixtures\\Test1\\Document', $model->document);
            $this->assertEquals($model->entity->id, $model->document->id);

            if ($model->getId() == 3) {
                $this->assertEquals(1, count($model->childrens));
            } else {
                $this->assertEquals(0, count($model->childrens));
            }

            if ($model->getId() == 5) {
                $this->assertInstanceOf('Pok\\PoolDBM\\Tests\\Fixtures\\Test1\\MultiModel', $model->parent);
            } else {
                $this->assertNull($model->parent);
            }
        }

        $this->assertEquals(5, Fixtures\Test1\EntityRepository::$count + Fixtures\Test1\DocumentRepository::$count);
    }
}
