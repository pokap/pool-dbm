<?php

namespace Pok\PoolDBM\Persisters;

use Pok\PoolDBM\Mapping\ClassMetadata;
use Pok\PoolDBM\Mapping\Definition\AssociationDefinition;

class CollectionCenter
{
    /**
     * @var AssociationDefinition
     */
    protected $definition;

    /**
     * @var ClassMetadata
     */
    protected $metadata;

    /**
     * @var mixed
     */
    protected $identifierRef;

    /**
     * @var array
     */
    protected $identifiers;

    /**
     * Constructor.
     *
     * @param AssociationDefinition $association   Definition association
     * @param ClassMetadata         $metadata      Class metadata of association model
     * @param mixed                 $coll          Model instance or ArrayCollection
     * @param mixed                 $identifierRef Identifier value for relation between collection and model reference
     */
    public function __construct(AssociationDefinition $association, ClassMetadata $metadata, $coll, $identifierRef)
    {
        $this->definition    = $association;
        $this->metadata      = $metadata;
        $this->identifierRef = $identifierRef;

        if ($association->isMany()) {
            $this->identifiers = array();

            foreach ($coll as $cc) {
                $id = $metadata->getIdentifierValue($cc);

                $this->identifiers[] = $id;
            }
        } else {
            $id = $metadata->getIdentifierValue($coll);

            $this->identifiers = array($id);
        }
    }

    /**
     * @return bool
     */
    public function isMany()
    {
        return $this->definition->isMany();
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->definition->getField();
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->definition->getTargetMultiModel();
    }

    /**
     * @return array
     */
    public function getManagers()
    {
        return $this->metadata->getFieldManagerNames();
    }

    /**
     * Returns identifier value for relation between collection and model reference.
     *
     * @return mixed
     */
    public function getIdentifierRef()
    {
        return $this->identifierRef;
    }

    /**
     * @param object $model
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getIdentifierMade($model)
    {
        if (!is_object($model)) {
            throw new \InvalidArgumentException(sprintf('You must give a object, "%s" given.', gettype($model)));
        }

        $methodMadeBy = 'get' . ucfirst($this->definition->getMadeBy());
        if (!method_exists($model, $methodMadeBy)) {
            throw new \RuntimeException(sprintf('Model "%s" must have method "%s".', get_class($model), $methodMadeBy));
        }

        $modelMade = $model->$methodMadeBy();
        if (!is_object($modelMade)) {
            throw new \RuntimeException(sprintf('Model "%s::%s()" must already return object, "%s" given.', get_class($model), $methodMadeBy, gettype($modelMade)));
        }

        $methodMadeField = 'get' . ucfirst($this->definition->getMadeField());
        if (!method_exists($model->{'get'.$this->definition->getMadeBy()}(), $modelMade)) {
            throw new \RuntimeException(sprintf('Model "%s" must have method "%s".', get_class($modelMade), $methodMadeField));
        }

        return $model->{'get'.ucfirst($this->definition->getMadeBy())}()->{'get'.ucfirst($this->definition->getMadeField())}();
    }

    /**
     * Returns list of identifiers.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->identifiers;
    }

    /**
     * Clean data.
     */
    public function clean()
    {
        $this->identifiers = array();
    }
}
