<?php

namespace Pok\PoolDBM\Mapping\Definition;

/**
 * ModelDefinition
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
class ModelDefinition
{
    protected $name;
    protected $managerName;
    protected $fields;
    protected $repositoryMethod;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $managerName
     * @param array  $fields
     */
    public function __construct($name, $managerName, array $fields = array())
    {
        $this->name        = $name;
        $this->managerName = $managerName;
        $this->fields      = $fields;
    }

    /**
     * @param string $repositoryMethod
     */
    public function setRepositoryMethod($repositoryMethod)
    {
        $this->repositoryMethod = $repositoryMethod;
    }

    /**
     * @return string
     */
    public function getRepositoryMethod()
    {
        return $this->repositoryMethod;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getManagerName()
    {
        return $this->managerName;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }
}
