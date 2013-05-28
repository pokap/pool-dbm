<?php

namespace Pok\PoolDBM\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;

use Pok\PoolDBM\Console\Command as PoolDBMCommand;
use Pok\PoolDBM\ModelManager;
use Pok\PoolDBM\Version;

/**
 * Handles running the Console Tools inside Symfony Console context.
 * Inspired by Doctrine\ORM\Tools\Console
 */
class ConsoleRunner
{
    /**
     * Create a Symfony Console HelperSet
     *
     * @param ModelManager $modelManager
     *
     * @return HelperSet
     */
    public static function createHelperSet(ModelManager $modelManager)
    {
        return new HelperSet(array(
            'modelManager' => new ModelManagerHelper($modelManager)
        ));
    }

    /**
     * Runs console with the given helperset.
     *
     * @param HelperSet                                    $helperSet
     * @param \Symfony\Component\Console\Command\Command[] $commands
     *
     * @return void
     */
    static public function run(HelperSet $helperSet, $commands = array())
    {
        $cli = new Application('PoolDBM Command Line Interface', Version::VERSION);
        $cli->setCatchExceptions(true);
        $cli->setHelperSet($helperSet);
        self::addDefaultCommands($cli);
        $cli->addCommands($commands);
        $cli->run();
    }

    /**
     * Sets default commands list.
     *
     * @param Application $cli
     */
    static public function addDefaultCommands(Application $cli)
    {
        $cli->addCommands(array(
            new PoolDBMCommand\GenerateMultiModelCommand()
        ));
    }

}
