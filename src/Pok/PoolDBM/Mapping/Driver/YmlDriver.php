<?php

namespace Pok\PoolDBM\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;

use Symfony\Component\Yaml\Yaml;

/**
 * YmlDriver.
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
class YmlDriver extends FileDriver
{
    const DEFAULT_FILE_EXTENSION = '.multi.yml';

    /**
     * {@inheritDoc}
     */
    public function __construct($locator, $fileExtension = self::DEFAULT_FILE_EXTENSION)
    {
        parent::__construct($locator, $fileExtension);
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        $element = $this->getElement($className);
        if (!$element) {
            return;
        }

        if (isset($element['repositoryClass'])) {
            $class->setCustomRepositoryClass((string) $element['repositoryClass']);
        }

        foreach ($element['models'] as $managerName => $model) {
            $this->addModel($class, $managerName, $model);
        }

        // mandatory, after register models
        $this->setModelReference($class, $element['modelReference']);
    }

    /**
     * @param ClassMetadata $class
     * @param array         $reference
     */
    protected function setModelReference(ClassMetadata $class, array $reference)
    {
        $class->setIdentifier($reference['manager'], $reference['field']);

        foreach ($reference['config'] as $name => $config) {
            switch ($name) {
                case 'reference':
                    $class->addRefenceIdentifier(
                        $config['manager'],
                        (isset($config['referenceField'])? $config['referenceField']: $reference['field']),
                        $config['field']
                    );
                    break;
                case 'idGenerator':
                    $class->setManagerReferenceGenerator($config['target-manager']);
                    break;
            }
        }
    }

    /**
     * @param ClassMetadata $class
     * @param string        $managerName
     * @param array         $model
     *
     * @throws \InvalidArgumentException When the definition cannot be parsed
     */
    protected function addModel(ClassMetadata $class, $managerName, array $model)
    {
        $class->addModel($managerName, $model['name'], $model['fields'], (isset($model['repositoryMethod'])? $model['repositoryMethod'] : null));
    }

    /**
     * {@inheritDoc}
     */
    protected function loadMappingFile($file)
    {
        return Yaml::parse($file);
    }
}
