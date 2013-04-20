<?php

namespace Pok\PoolDBM\Mapping\Definition;

/**
 * AssociationDefinition
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
class AssociationDefinition
{
    protected $field;
    protected $targetMultiModel;
    protected $isCollection;
    protected $cascade;
    protected $referenceField;

    /**
     * Constructor.
     *
     * @param string  $field
     * @param string  $targetMultiModel
     * @param boolean $isCollection
     */
    public function __construct($field, $targetMultiModel, $isCollection)
    {
        $this->field            = $field;
        $this->targetMultiModel = $targetMultiModel;
        $this->isCollection     = $isCollection;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return string
     */
    public function getTargetMultiModel()
    {
        return $this->targetMultiModel;
    }

    /**
     * @param string $referenceField
     *
     * @return AssociationDefinition
     */
    public function setReferenceField($referenceField)
    {
        $this->referenceField = $referenceField;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getReferenceField()
    {
        return $this->referenceField;
    }

    /**
     * @param array $cascade
     *
     * @return AssociationDefinition
     */
    public function setCascade(array $cascade)
    {
        $this->cascade = $cascade;

        return $this;
    }

    /**
     * @return null|array
     */
    public function getCascade()
    {
        return $this->cascade;
    }

    /**
     * @return boolean
     */
    public function isOne()
    {
        return !$this->isCollection;
    }

    /**
     * @return boolean
     */
    public function isMany()
    {
        return $this->isCollection;
    }
}
