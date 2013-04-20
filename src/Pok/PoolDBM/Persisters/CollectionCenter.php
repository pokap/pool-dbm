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
     * @var array
     */
    protected $identifiers;

    /**
     * Constructor.
     *
     * @param AssociationDefinition $association
     * @param ClassMetadata         $metadata
     * @param mixed                 $coll        Model instance or ArrayCollection
     */
    public function __construct(AssociationDefinition $association, ClassMetadata $metadata, $coll)
    {
        $this->definition  = $association;
        $this->metadata    = $metadata;

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
