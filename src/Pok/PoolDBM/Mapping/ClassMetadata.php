<?php

namespace Pok\PoolDBM\Mapping;

/**
 * ClassMetadata.
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
class ClassMetadata extends ClassMetadataInfo
{
    /**
     * The prototype from which new instances of the mapped class are created.
     *
     * @var object
     */
    private $prototype;

    /**
     * @return array
     */
    public function __sleep()
    {
        // This metadata is always serialized/cached.
        $serialized = array(
            'fieldMappings',
            'identifierField',
            'identifierManager',
            'name',
        );

        if ($this->customRepositoryClassName) {
            $serialized[] = 'customRepositoryClassName';
        }

        return $serialized;
    }

    /**
     * Creates a new instance of the mapped class, without invoking the constructor.
     *
     * @return object
     */
    public function newInstance()
    {
        if ($this->prototype === null) {
            $this->prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($this->name), $this->name));
        }

        return clone $this->prototype;
    }
}
