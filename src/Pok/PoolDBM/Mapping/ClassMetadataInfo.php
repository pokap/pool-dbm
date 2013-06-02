<?php

namespace Pok\PoolDBM\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ReflectionClass;

use Pok\PoolDBM\Mapping\Definition\AssociationDefinition;
use Pok\PoolDBM\Mapping\Definition\ModelDefinition;

/**
 * Class ClassMetadataInfo
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
class ClassMetadataInfo implements ClassMetadataInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $customRepositoryClassName;

    /**
     * @var string
     */
    protected $identifierField;

    /**
     * @var string
     */
    protected $identifierManager;

    /**
     * @var ModelDefinition[]
     */
    protected $fieldMappings;

    /**
     * @var array
     */
    protected $identifierReferences;

    /**
     * @var AssociationDefinition[]
     */
    protected $associationMappings;

    /**
     * @var string
     */
    protected $referenceGeneratorManager;

    /**
     * The ReflectionClass instance of the mapped class.
     *
     * @var \ReflectionClass
     */
    public $reflClass;

    /**
     * Constructor.
     *
     * @param string $modelName
     */
    public function __construct($modelName)
    {
        $this->name = $modelName;

        $this->fieldMappings       = array();
        $this->associationMappings = array();
    }

    /**
     * Return model name for current class metadata.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $fieldName
     *
     * @return boolean
     */
    public function isIdentifier($fieldName)
    {
        return $this->identifierField === $fieldName;
    }

    /**
     * @param string $manager
     * @param string $field
     */
    public function setIdentifier($manager, $field)
    {
        $this->identifierField   = $field;
        $this->identifierManager = $manager;
    }

    /**
     * Check if identifier has been setted.
     *
     * @return boolean
     */
    public function hasIdentifier()
    {
        return !empty($this->identifierField) && !empty($this->identifierManager);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->identifierField;
    }

    /**
     * @param string $manager
     * @param string $referenceField
     * @param string $field
     */
    public function addRefenceIdentifier($manager, $referenceField, $field)
    {
        $rule = new \stdClass();
        $rule->referenceField = $referenceField;
        $rule->field          = $field;

        $this->identifierReferences[$manager] = $rule;
    }

    /**
     * Returns field idendifier given by manager.
     * If manager not specific, returns identifier field of model reference.
     *
     * @param string $manager
     *
     * @return \stdClass
     */
    public function getReferenceIdentifier($manager)
    {
        if (!isset($this->identifierReferences[$manager])) {
            $rule = new \stdClass();
            $rule->referenceField = $this->identifierField;
            $rule->field          = $this->identifierField;

            return $rule;
        }

        return $this->identifierReferences[$manager];
    }

    /**
     * @return string
     */
    public function getManagerIdentifier()
    {
        return $this->identifierManager;
    }

    /**
     * @return string
     */
    public function getFieldIdentifier()
    {
        return $this->identifierField;
    }

    /**
     * Get identifier field names of this class.
     *
     * @return array
     */
    public function getIdentifierFieldNames()
    {
        return array($this->identifierField);
    }

    /**
     * Checks whether the class has a (mapped) field with a certain name.
     *
     * @param string $fieldName
     *
     * @return boolean
     */
    public function hasField($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]);
    }

    /**
     * Registers a custom repository class for the model class.
     *
     * @param string $mapperClassName The class name of the custom mapper.
     */
    public function setCustomRepositoryClass($repositoryClassName)
    {
        $this->customRepositoryClassName = $repositoryClassName;
    }

    /**
     * Returns a custom repository class for the model class.
     *
     * @return string Name of repository
     */
    public function getCustomRepositoryClassName()
    {
        return $this->customRepositoryClassName;
    }

    /**
     * Map fields per model.
     *
     * @param string $field             Manager name
     * @param string $modelName
     * @param array  $subFields         (optional)
     * @param string $repository_method (optional)
     *
     * @return ModelDefinition
     */
    public function addModel($field, $modelName, array $subFields = array(), $repository_method = null)
    {
        $mapping = new ModelDefinition($modelName, $field, $subFields);
        $mapping->setRepositoryMethod($repository_method);

        $this->fieldMappings[$field] = $mapping;

        return $mapping;
    }

    /**
     * Sets reference generator manager.
     *
     * @param string $targetManager
     */
    public function setManagerReferenceGenerator($targetManager)
    {
        $this->referenceGeneratorManager = $targetManager;
    }

    /**
     * Returns reference generator manager.
     *
     * @return string
     */
    public function getManagerReferenceGenerator()
    {
        return $this->referenceGeneratorManager? : $this->identifierManager;
    }

    /**
     * Adding associtation mapping between multi-model.
     *
     * @param boolean $isCollection
     * @param string  $field
     * @param string  $targetMultiModel
     * @param string  $referenceField   (optional)
     * @param array   $cascade          (optional)
     *
     * @return AssociationDefinition
     */
    public function addAssociation($isCollection, $field, $targetMultiModel, $referenceField = null, array $cascade = array())
    {
        $mapping = new AssociationDefinition($field, $targetMultiModel, $isCollection);
        $mapping->setCascade($cascade);
        $mapping->setReferenceField($referenceField);

        $this->associationMappings[$field] = $mapping;

        return $mapping;
    }

    /**
     * @param object $model
     *
     * @return mixed
     */
    public function getIdentifierValues($model)
    {
        return array($this->identifierField => $this->getIdentifierValue($model));
    }

    /**
     * @param object $model
     * @param string $manager (optional)
     *
     * @return mixed
     */
    public function getIdentifierValue($model, $manager = null)
    {
        $field = (null !== $manager)? $this->getReferenceIdentifier($manager)->referenceField : $this->identifierField;

        return $model->{'get' . ucfirst($field)}();
    }

    /**
     * @param string $fieldName
     *
     * @eturn boolean
     */
    public function hasFieldMapping($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]);
    }

    /**
     * Gets the mapping of a field.
     *
     * @param string $fieldName The field name
     *
     * @return ModelDefinition The field mapping
     */
    public function getFieldMapping($fieldName)
    {
        return $this->fieldMappings[$fieldName];
    }

    /**
     * Returns list of model definitions.
     *
     * @return ModelDefinition[]
     */
    public function getFieldMappings()
    {
        return $this->fieldMappings;
    }

    /**
     * @return array
     */
    public function getFieldManagerNames()
    {
        return array_keys($this->fieldMappings);
    }

    /**
     * @return array
     */
    public function getFieldNames()
    {
        $fields = array();
        foreach ($this->fieldMappings as $mapping) {
            $fields = array_merge($fields, $mapping->getFields());
        }

        return $fields;
    }

    /**
     * @param string $fieldName
     *
     * @return ModelDefinition
     *
     * @throws \InvalidArgumentException
     */
    public function getModelDefinitionByFieldName($fieldName)
    {
        foreach ($this->fieldMappings as $mapping) {
            if (in_array($fieldName, $mapping->getFields())) {
                return $mapping;
            }
        }

        throw new \InvalidArgumentException(sprintf('%s does not exists in %s', $fieldName, $this->name));
    }

    /**
     * {@inheritdoc}
     */
    public function getReflectionClass()
    {
        if (!$this->reflClass) {
            $this->reflClass = new \ReflectionClass($this->name);
        }

        return $this->reflClass;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAssociation($fieldName)
    {
        return isset($this->associationMappings[$fieldName]);
    }

    /**
     * {@inheritdoc}
     */
    public function isSingleValuedAssociation($fieldName)
    {
        return $this->associationMappings[$fieldName]->isOne();
    }

    /**
     * {@inheritdoc}
     */
    public function isCollectionValuedAssociation($fieldName)
    {
        return $this->associationMappings[$fieldName]->isMany();
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociationNames()
    {
        return array_keys($this->associationMappings);
    }

    /**
     * Returns list of association field per reference model.
     *
     * @eturn array
     */
    public function getAssociationReferenceNames()
    {
        $referenceNames = array();
        foreach ($this->associationMappings as $assoc) {
            $referenceNames[] = $assoc->getReferenceField() ?: $assoc->getField();
        }

        return $referenceNames;
    }

    /**
     * Returns list of association mappings.
     *
     * @return AssociationDefinition[]
     */
    public function getAssociationDefinitions()
    {
        return $this->associationMappings;
    }

    /**
     * @param string $field
     *
     * @return AssociationDefinition
     */
    public function getAssociationDefinition($field)
    {
        return $this->associationMappings[$field];
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeOfField($fieldName)
    {
        throw new \LogicException(sprintf('Field has no type, look mapping definition of "%s".', $this->getModelDefinitionByFieldName($fieldName)->getName()));
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociationTargetClass($assocName)
    {
        return $this->associationMappings[$assocName]->getTargetMultiModel();
    }

    /**
     * {@inheritdoc}
     */
    public function isAssociationInverseSide($assocName)
    {
        throw new \BadMethodCallException(__METHOD__.'() is not implemented yet.');
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociationMappedByTargetField($assocName)
    {
        throw new \BadMethodCallException(__METHOD__.'() is not implemented yet.');
    }
}
