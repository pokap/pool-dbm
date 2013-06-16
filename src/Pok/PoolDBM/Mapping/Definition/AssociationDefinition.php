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
    protected $references;

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

        $this->references = array();
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
     * @param array $references
     *
     * @return AssociationDefinition
     */
    public function setReferences(array $references)
    {
        $this->references = $references;

        return $this;
    }

    /**
     * Returns list of reference field per manager name.
     *
     * @return array
     */
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * @param string $managerName
     *
     * @return string
     */
    public function getReferenceField($managerName)
    {
        return isset($this->references[$managerName])? $this->references[$managerName] : $this->field;
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
