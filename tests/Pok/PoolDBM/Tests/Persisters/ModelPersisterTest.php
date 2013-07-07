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
    public function test1()
    {
        $metadata = new ClassMetadata($this->getFixtures('Test1\\MultiModel'));
        $metadata->addModel('entity', $this->getFixtures('Test1\\Entity'), array(), 'findByIds');
        $metadata->addModel('document', $this->getFixtures('Test1\\Document'), array(), 'findByIds');
        $metadata->setIdentifier('entity', 'id');
        $metadata->addAssociation(false, 'parent', $this->getFixtures('Test1\\MultiModel'));
        $metadata->addAssociation(true, 'childrens', $this->getFixtures('Test1\\MultiModel'));

        $metadataFactory = $this->getMock('Pok\\PoolDBM\\Mapping\\ClassMetadataFactory', array('getMetadataFor', 'setModelManager'));
        $metadataFactory->expects($this->any())->method('getMetadataFor')->will($this->returnValue($metadata));

        $pool = new Pool();
        $pool->addManager('entity',   new Fixtures\Test1\EntityManager());
        $pool->addManager('document', new Fixtures\Test1\DocumentManager());

        $manager = new ModelManager($pool, $metadataFactory);
        $persisters = new ModelPersister($manager, new UnitOfWork($manager), $metadata);

        $this->assertInstanceOf('Pok\\PoolDBM\\Mapping\\ClassMetadata', $persisters->getClassMetadata());

        // load 1
        $model = $persisters->load(1);
        $this->assertInstanceOf($this->getFixtures('Test1\\MultiModel'), $model);
        $this->assertInstanceOf($this->getFixtures('Test1\\Entity'), $model->entity);
        $this->assertEquals(1, $model->entity->id);
        $this->assertInstanceOf($this->getFixtures('Test1\\Document'), $model->document);
        $this->assertEquals($model->entity->id, $model->document->id);

        // load 3
        $model = $persisters->load(3);
        $this->assertInstanceOf($this->getFixtures('Test1\\MultiModel'), $model);
        $this->assertInstanceOf($this->getFixtures('Test1\\Entity'), $model->entity);
        $this->assertEquals(3, $model->entity->id);
        $this->assertInstanceOf($this->getFixtures('Test1\\Document'), $model->document);
        $this->assertEquals($model->entity->id, $model->document->id);
        $this->assertEquals(2, count($model->childrens));

        // load 5
        $model = $persisters->load(5);
        $this->assertInstanceOf($this->getFixtures('Test1\\MultiModel'), $model);
        $this->assertInstanceOf($this->getFixtures('Test1\\Entity'), $model->entity);
        $this->assertEquals(5, $model->entity->id);
        $this->assertInstanceOf($this->getFixtures('Test1\\Document'), $model->document);
        $this->assertEquals($model->entity->id, $model->document->id);
        $this->assertInstanceOf($this->getFixtures('Test1\\MultiModel'), $model->parent);

        // load all
        $models = $persisters->loadAll();

        $this->assertEquals(5, count($models));

        foreach ($models as $model) {
            $this->assertInstanceOf($this->getFixtures('Test1\\MultiModel'), $model);

            $this->assertInstanceOf($this->getFixtures('Test1\\Entity'), $model->entity);
            $this->assertInstanceOf($this->getFixtures('Test1\\Document'), $model->document);
            $this->assertEquals($model->entity->id, $model->document->id);

            if ($model->getId() == 3) {
                $this->assertEquals(2, count($model->childrens));
            } else {
                $this->assertEquals(0, count($model->childrens));
            }

            if (in_array($model->getId(), array(1,5))) {
                $this->assertInstanceOf($this->getFixtures('Test1\\MultiModel'), $model->parent);
            } else {
                $this->assertNull($model->parent);
            }
        }

        $this->assertEquals(12, Fixtures\Test1\EntityRepository::$count + Fixtures\Test1\DocumentRepository::$count);
    }

    public function test2()
    {
        $metadatas = array();

        $metadata = new ClassMetadata($this->getFixtures('Test2\\Model\\User'));
        $metadata->addModel('entity', $this->getFixtures('Test2\\Entity\\User'), array(), 'findByIds');
        $metadata->addModel('document', $this->getFixtures('Test2\\Document\\User'), array(), 'findByIds');
        $metadata->setIdentifier('entity', 'id');
        $metadata->addAssociation(true, 'groups', $this->getFixtures('Test2\\Model\\Group'));

        $metadatas[$this->getFixtures('Test2\\Model\\User')] = $metadata;

        $metadata = new ClassMetadata($this->getFixtures('Test2\\Model\\Group'));
        $metadata->addModel('entity', $this->getFixtures('Test2\\Entity\\Group'), array(), 'findByIds');
        $metadata->addModel('document', $this->getFixtures('Test2\\Document\\Group'), array(), 'findByIds');
        $metadata->setIdentifier('entity', 'id');
        $metadata->addIdentifierReference('document', 'documentId', 'id');
        $metadata->addAssociation(true, 'users', $this->getFixtures('Test2\\Model\\User'));

        $metadatas[$this->getFixtures('Test2\\Model\\Group')] = $metadata;

        $metadataFactory = $this->getMock('Pok\\PoolDBM\\Mapping\\ClassMetadataFactory', array('getMetadataFor', 'setModelManager'));
        $metadataFactory->expects($this->any())->method('getMetadataFor')->will($this->returnCallback(function ($className) use ($metadatas) {
            if (!isset($metadatas[$className])) {
                throw new \RuntimeException(sprintf('Class "%s" not register.', $className));
            }

            return $metadatas[$className];
        }));

        $pool = new Pool();
        $pool->addManager('entity',   new Fixtures\Test2\EntityManager());
        $pool->addManager('document', new Fixtures\Test2\DocumentManager());

        $manager = new ModelManager($pool, $metadataFactory);

        // User
        $persisters = new ModelPersister($manager, new UnitOfWork($manager), $metadatas[$this->getFixtures('Test2\\Model\\User')]);

        $this->assertInstanceOf('Pok\\PoolDBM\\Mapping\\ClassMetadata', $persisters->getClassMetadata());

        // load 1
        $model = $persisters->load(1);
        $this->assertInstanceOf($this->getFixtures('Test2\\Model\\User'), $model);
        $this->assertInstanceOf($this->getFixtures('Test2\\Entity\\User'), $model->entity);
        $this->assertEquals(1, $model->entity->id);
        $this->assertInstanceOf($this->getFixtures('Test2\\Document\\User'), $model->document);
        $this->assertEquals($model->entity->id, $model->document->id);
        $this->assertEquals(2, count($model->groups));

        // Group
        $persisters = new ModelPersister($manager, new UnitOfWork($manager), $metadatas[$this->getFixtures('Test2\\Model\\Group')]);

        $this->assertInstanceOf('Pok\\PoolDBM\\Mapping\\ClassMetadata', $persisters->getClassMetadata());

        // load 1
        $model = $persisters->load(1);
        $this->assertInstanceOf($this->getFixtures('Test2\\Model\\Group'), $model);
        $this->assertInstanceOf($this->getFixtures('Test2\\Entity\\Group'), $model->entity);
        $this->assertEquals(1, $model->entity->id);
        $this->assertInstanceOf($this->getFixtures('Test2\\Document\\Group'), $model->document);
        $this->assertEquals($model->entity->documentId, $model->document->id);
        $this->assertEquals(2, count($model->users));
    }

    /**
     * Returns complete namespace from fixtures tests.
     *
     * @param $namespace
     *
     * @return string
     */
    private function getFixtures($namespace)
    {
        return 'Pok\\PoolDBM\\Tests\\Fixtures\\' . $namespace;
    }
}
