<?php

namespace Pok\PoolDBM\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;

use Pok\PoolDBM\Mapping\ClassMetadata as ClassMetadataInfo;

/**
 * XmlDriver.
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
class XmlDriver extends FileDriver
{
    const DEFAULT_FILE_EXTENSION = '.multi.xml';

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
        /* @var \Pok\PoolDBM\Mapping\ClassMetadata $class */
        /* @var \SimpleXMLElement $xmlRoot */
        $xmlRoot = $this->getElement($className);
        if (!$xmlRoot) {
            return;
        }

        if (isset($xmlRoot['repository-class'])) {
            $class->setCustomRepositoryClass((string) $xmlRoot['repository-class']);
        }

        foreach ($xmlRoot->model as $model) {
            $this->addModel($class, $model);
        }

        // mandatory, after register models
        $this->setModelReference($class, $xmlRoot->{'model-reference'});

        // associations
        foreach ($xmlRoot->{'relation-one'} as $reference) {
            $this->addAssociation($class, $reference, false);
        }
        foreach ($xmlRoot->{'relation-many'} as $reference) {
            $this->addAssociation($class, $reference, true);
        }
    }

    /**
     * @param ClassMetadataInfo $class
     * @param \SimpleXMLElement $reference
     * @param boolean           $isCollection
     */
    protected function addAssociation(ClassMetadataInfo $class, \SimpleXMLElement $reference, $isCollection)
    {
        $references = array();

        foreach ($reference as $config) {
            /** @var \SimpleXMLElement $config */
            if ('field-reference' === $config->getName()) {
                $references[(string) $config['manager']] = (string) $config['field'];
            }
        }

        $compatible = array();
        if (isset($reference['compatible'])) {
            $compatible = explode(',', (string) $reference['compatible']);
            $compatible = array_map('trim', $compatible);
        }

        $class->addAssociation(
            $isCollection,
            (string) $reference['field'],
            (string) $reference['target-model'],
            $compatible,
            $references
        );
    }

    /**
     * @param ClassMetadataInfo $class
     * @param \SimpleXMLElement $reference
     */
    protected function setModelReference(ClassMetadataInfo $class, \SimpleXMLElement $reference)
    {
        $parameters = $reference->attributes();

        $class->setIdentifier((string) $parameters['manager'], (string) $parameters['field']);

        foreach ($reference as $config) {
            /** @var \SimpleXMLElement $config */
            switch ($config->getName()) {
                case 'reference':
                    $class->addIdentifierReference(
                        (string) $config['manager'],
                        (isset($config['reference-field'])? (string) $config['reference-field']: (string) $parameters['field']),
                        (string) $config['field']
                    );
                    break;
                case 'id-generator':
                    $class->setManagerReferenceGenerator((string) $config['target-manager']);
                    break;
            }
        }
    }

    /**
     * @param ClassMetadataInfo $class
     * @param \SimpleXMLElement $model
     *
     * @throws \InvalidArgumentException When the definition cannot be parsed
     */
    protected function addModel(ClassMetadataInfo $class, \SimpleXMLElement $model)
    {
        $fields = array();

        foreach ($model as $field) {
            /** @var \SimpleXMLElement $field */
            if ('field' === $field->getName()) {
                $fields[] = (string) $field['name'];
            } else {
                throw new \InvalidArgumentException(sprintf('Unable to parse tag "%s"', $field->getName()));
            }
        }

        $class->addModel((string) $model['manager'], (string) $model['name'], $fields, (isset($model['repository-method'])? (string) $model['repository-method'] : null));
    }

    /**
     * {@inheritDoc}
     */
    protected function loadMappingFile($file)
    {
        $xmlElement = simplexml_load_file($file);

        return array((string) $xmlElement['model'] => $xmlElement);
    }
}
