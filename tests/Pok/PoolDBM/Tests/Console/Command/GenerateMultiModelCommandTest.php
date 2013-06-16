<?php

namespace Pok\PoolDBM\Tests\Console\Command;

use Pok\PoolDBM\Tests\Mocks\MetadataDriverMock;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Pok\PoolDBM\Console\ConsoleRunner;
use Pok\PoolDBM\Console\Command\GenerateMultiModelCommand;
use Pok\PoolDBM\Manager\Pool;
use Pok\PoolDBM\ModelManager;

use Pok\PoolDBM\Tests\Mocks\ObjectManagerMock;

class GenerateMultiModelCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $metadata = new \Pok\PoolDBM\Mapping\ClassMetadata(__NAMESPACE__ . '\\ModelTest');
        $metadata->addModel('entity', __NAMESPACE__ . '\\EntityTest', array());
        $metadata->setIdentifier('entity', 'id');

        $metadataFactory = $this->getMock('Pok\\PoolDBM\\Mapping\\ClassMetadataFactory', array('getMetadataFor', 'setModelManager'));
        $metadataFactory->expects($this->any())->method('getMetadataFor')->will($this->returnValue($metadata));

        $pool = new Pool();
        $pool->addManager('entity', new ObjectManagerMock());

        $manager = new ModelManager($pool, $metadataFactory);
        $manager->setMetadataDriverImpl(new MetadataDriverMock(array(__NAMESPACE__ . '\\ModelTest')));

        $application = new Application();
        $application->setHelperSet(ConsoleRunner::createHelperSet($manager));
        $application->add(new GenerateMultiModelCommand());

        $command = $application->find('pok:pool-dbm:generate:model');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), 'dest-path' => sys_get_temp_dir(), '--auto-update' => true));
    }
}

class EntityTest
{
    public $id;

    /**
     * Returns ID.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}
