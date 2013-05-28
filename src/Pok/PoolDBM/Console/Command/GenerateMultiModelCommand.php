<?php

namespace Pok\PoolDBM\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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
        $this->setName('pok:pool-dbm:model:generate');
        $this->setDescription('Generate multi-model');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filesystem = new Filesystem();
        $manager  = $this->getModelManager();
        $metadata = $manager->getMetadataDriverImpl();

        $dialog = $this->getHelper('dialog');

        foreach ($metadata->getAllClassNames() as $className) {
            $data       = $this->getElement($metadata->getDrivers(), $className);
            $parameters = $this->buildParameters($data);

            $filename = sprintf('%s%s%s.php', $data['dir'], DIRECTORY_SEPARATOR, $parameters['model_name']);

            if ($filesystem->exists($filename) && !$dialog->askConfirmation($output, sprintf('<question>MultiModel "%s" already exists, Do you want to overwrite it? (y,N) </question>', $parameters['model_name']), false)) {
                continue;
            }

            if (!$filesystem->exists($data['dir'])) {
                $filesystem->mkdir($data['dir']);
            }

            $state = file_put_contents($filename, $this->getTimedTwigEngine()->render($this->getTemplate(), $parameters));

            $output->writeln(sprintf('%s %s', ($state ? 'Create' : '<error>Failed</error>'), $filename));
        }

        $output->writeln('');
        $output->writeln('<info>Done.</info>');
    }

    /**
     * Build parameters with data recovered by the driver.
     *
     * @param array $data
     *
     * @return array
     */
    protected function buildParameters(array $data)
    {
        $managers = array();
        foreach ($data['models'] as $model) {

            $managers[$model['manager']] = array(
                'namespace' => '\\' . $model['name'],
                'methods'   => array()
            );

            if ($data['identifier']['manager'] === $model['manager']) {
                $model['fields'][] = $data['identifier']['field'];
            }

            $pattern = self::patternDeclared($model['fields']);

            $refl = new \ReflectionClass($model['name']);
            foreach ($refl->getMethods() as $method) {
                if (!$method->isPublic() || $method->isStatic() || $method->isConstructor() || $method->isDestructor() || $method->isAbstract()) {
                    continue;
                }

                preg_match($pattern, $method->getName(), $matches);
                if (empty($matches)) {
                    continue;
                }

                $arg = array(
                    'comment'   => $method->getDocComment(),
                    'name'      => $method->getName(),
                    'type'      => in_array($matches[1], array('get','is','has','all')) ? 'getter' : 'setter',
                    'arguments' => Reflector::parameters($method->getParameters())
                );

                $managers[$model['manager']]['methods'][] = $arg;
            }
        }

        $occ = strrpos($data['class'], '\\');

        return array(
            'model_namespace' => substr($data['class'], 0, $occ),
            'model_name'      => substr($data['class'], $occ + 1),
            'managers'        => $managers
        );
    }

    /**
     * @return \Pok\PoolDBM\ModelManager
     */
    protected function getModelManager()
    {
        return $this->getHelper('modelManager');
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

    /**
     * @param \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver[] $drivers
     * @param string                                                      $className
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    private function getElement(array $drivers, $className)
    {
        foreach ($drivers as $namespace => $driver) {
            if (strpos($className, $namespace) === 0) {
                $result = $this->getMetadata($driver->getElement($className));

                $result['dir'] = $this->getDirectory($driver->getLocator()->getNamespacePrefixes(), $className);

                return $result;
            }
        }

        throw new \RuntimeException(sprintf('Invalid drivers with "%s".', $className));
    }

    /**
     * @param \SimpleXMLElement $xml
     *
     * @return array
     */
    private function getMetadata(\SimpleXMLElement $xml)
    {
        $result = array();

        $result['class'] = (string) $xml['model'];

        $result['identifier'] = array(
            'manager' => (string) $xml->{'model-reference'}['manager'],
            'field'   => (string) $xml->{'model-reference'}['field']
        );

        foreach ($xml->model as $model) {
            $definition = array(
                'name'   => (string) $model['name'],
                'manager' => (string) $model['manager'],
                'fields'  => array()
            );

            foreach ($model as $field) {
                if ('field' !== $field->getName()) {
                    continue;
                }

                foreach ($field->attributes() as $name => $value) {
                    if ('name' !== $name) {
                        continue;
                    }

                    $definition['fields'][] = (string) $value[0];
                }
            }

            $result['models'][] = $definition;
        }

        return $result;
    }

    protected static function render($modelNamespace, $modelName, \Closure $subModels, \Closure $setsSubModels, \Closure $methodSubModels, \Closure $aliasMethodSubModels)
    {
        return <<<TPL
<?php

namespace $modelNamespace;

class $modelName
{
$subModels
    /**
     * Constructor.
     */
    public function __construct()
    {
$setsSubModels
    }

$methodSubModels
$aliasMethodSubModels
}

TPL;
    }

    /**
     * @param array  $prefixes
     * @param string $className
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    private function getDirectory(array $prefixes, $className)
    {
        foreach ($prefixes as $dir => $namespace) {
            if (0 !== strpos($className, $namespace)) {
                continue;
            }

            $dir = substr($dir, 0, strrpos($dir, 'Bundle' . DIRECTORY_SEPARATOR) + 7);

            $namespace = substr($namespace, strrpos($namespace, 'Bundle\\') + 7);

            return $dir . str_replace('\\', DIRECTORY_SEPARATOR, $namespace);
        }

        throw new \RuntimeException('Unknown dir class.');
    }
}
