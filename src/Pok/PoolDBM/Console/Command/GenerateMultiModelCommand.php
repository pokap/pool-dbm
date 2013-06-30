<?php

namespace Pok\PoolDBM\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

use Pok\PoolDBM\Util\Reflector;

/**
 * Generate multi-model with models definitions.
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
class GenerateMultiModelCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pok:pool-dbm:generate:model');
        $this->setDescription('Generate multi-model');

        $this->addArgument('dest-path', InputArgument::REQUIRED,
            'The path to generate your model classes.'
        );
        $this->addOption('template-name', 'tn', InputOption::VALUE_OPTIONAL,
            'Template filename for generate your model classes.', 'model_generate.php.twig'
        );
        $this->addOption('extends', null, InputOption::VALUE_OPTIONAL,
            'Defines a base class to be extended by generated model classes.'
        );
        $this->addOption('auto-update', 'auto-up', InputOption::VALUE_OPTIONAL,
            'Flag to define if generator should only update model if it exists, without interaction.', false
        );
        $this->addOption('no-update', 'no-up', InputOption::VALUE_OPTIONAL,
            'Flag to define if generator should only pass model if it exists, without interaction.', false
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager     = $this->getModelManager();
        $metadata    = $manager->getMetadataDriverImpl();
        $no_update   = $input->getOption('no-update');
        $auto_update = !$no_update && $input->getOption('auto-update');
        $path        = $input->getArgument('dest-path');

        $filesystem = new Filesystem();

        if (!$filesystem->exists($path)) {
            $filesystem->mkdir($path);
        }

        $dialog = $this->getHelper('dialog');

        foreach ($metadata->getAllClassNames() as $className) {
            $parameters = $this->buildParameters($className);
            $parameters['extends'] = $input->getOption('extends');

            $filename = sprintf('%s%s%s.php', $path, DIRECTORY_SEPARATOR, $parameters['model_name']);

            if (!$auto_update && $filesystem->exists($filename) && ($no_update || !$dialog->askConfirmation($output, sprintf('<question>MultiModel "%s" already exists, Do you want to overwrite it? (y,N) </question>', $parameters['model_name']), false))) {
                continue;
            }

            $state = file_put_contents($filename, $this->getHelper('template')->render($input->getOption('template-name'), $parameters));

            $output->writeln(sprintf('%s %s', ($state ? 'Create' : '<error>Failed</error>'), $filename));
        }

        $output->writeln('<info>Done.</info>');
    }

    /**
     * Build parameters with data recovered by the driver.
     *
     * @param string $className
     *
     * @return array
     */
    protected function buildParameters($className)
    {
        /** @var \Pok\PoolDBM\Mapping\ClassMetadata $metadata */
        $metadata = $this->getModelManager()->getClassMetadata($className);

        $managers = array();
        foreach ($metadata->getFieldMappings() as $model) {

            $managers[$model->getManagerName()] = array(
                'namespace' => '\\' . $model->getName(),
                'methods'   => array()
            );

            $fields = $model->getFields();
            if ($metadata->getManagerIdentifier() === $model->getManagerName()) {
                $fields[] = $metadata->getFieldIdentifier();
            }

            $pattern = self::patternDeclared($fields);

            $refl = new \ReflectionClass($model->getName());
            foreach ($refl->getMethods() as $method) {
                if (!$method->isPublic() || $method->isStatic() || $method->isConstructor() || $method->isDestructor() || $method->isAbstract()) {
                    continue;
                }

                preg_match($pattern, $method->getName(), $matches);
                if (empty($matches)) {
                    continue;
                }

                $arg = array(
                    'comment'    => $method->getDocComment(),
                    'name'       => $method->getName(),
                    'type'       => in_array($matches[1], array('get','is','has','all','check')) ? 'getter' : 'setter',
                    'arguments'  => Reflector::parameters($method->getParameters()),
                    'parameters' => $method->getParameters()
                );

                $managers[$model->getManagerName()]['methods'][] = $arg;
            }
        }

        $associations = new Bag();
        foreach ($metadata->getAssociationDefinitions() as $definition) {
            if ($definition->isMany()) {
                $associations->many[$definition->getField()] = array(
                    'fields'    => $definition->getReferences(),
                    'className' => $definition->getTargetMultiModel()
                );
            } else {
                $associations->one[$definition->getField()] = array(
                    'className' => $definition->getTargetMultiModel()
                );
            }
        }

        $occ = strrpos($metadata->getName(), '\\');

        return array(
            'model_namespace'   => substr($metadata->getName(), 0, $occ),
            'model_name'        => substr($metadata->getName(), $occ + 1),
            'managers'          => $managers,
            'associations'      => $associations
        );
    }

    /**
     * @return \Pok\PoolDBM\ModelManager
     */
    protected function getModelManager()
    {
        return $this->getHelper('modelManager')->getModelManager();
    }

    /**
     * @param array $fields
     *
     * @return string
     */
    private static function patternDeclared(array $fields)
    {
        foreach ($fields as $i => $field) {
            $fields[$i] = ucfirst($field);
        }

        return sprintf('`^([a-z]+)(%s)$`', implode('|', $fields));
    }
}

/**
 * Class Bag
 *
 * @access private
 */
class Bag
{
    public $one;
    public $many;

    /**
     * Constructor.
     *
     * @param array $one
     * @param array $many
     */
    public function __construct(array $one = array(), array $many = array())
    {
        $this->one  = $one;
        $this->many = $many;
    }

    /**
     * Returns all associations
     *
     * @return array
     */
    public function getAll()
    {
        return array_merge($this->one, $this->many);
    }
}
