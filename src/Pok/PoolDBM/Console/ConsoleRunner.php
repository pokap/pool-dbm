<?php

namespace Pok\PoolDBM\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\HelperSet;

use Pok\PoolDBM\Console\Helper\ModelManagerHelper;
use Pok\PoolDBM\Console\Helper\TemplateEngineHelper;
use Pok\PoolDBM\Console\Helper\TemplateHelperInterface;
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
     * @param ModelManager            $modelManager
     * @param TemplateHelperInterface $template     (optional)
     * @param string                  $cache_dir    (optional)
     *
     * @return HelperSet
     */
    public static function createHelperSet(ModelManager $modelManager, TemplateHelperInterface $template = null, $cache_dir = null)
    {
        $resources_dir = __DIR__ . '/../Resources';
        $cache_dir     = $cache_dir?: sys_get_temp_dir() . '/php/pool-dbm/cache';

        return new HelperSet(array(
            'dialog'       => new DialogHelper(),
            'modelManager' => new ModelManagerHelper($modelManager),
            'template'     => ($template?: new TemplateEngineHelper($resources_dir, array('cache' => $cache_dir)))
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
    public static function run(HelperSet $helperSet, $commands = array())
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
    public static function addDefaultCommands(Application $cli)
    {
        $cli->addCommands(array(
            new PoolDBMCommand\GenerateMultiModelCommand()
        ));
    }
}
