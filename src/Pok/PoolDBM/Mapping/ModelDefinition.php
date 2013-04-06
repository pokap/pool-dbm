<?php

namespace Pok\PoolDBM\Mapping;

class ModelDefinition
{
    protected $name;
    protected $managerName;
    protected $fields;
    protected $repositoryMethod;

    public function __construct($name, $managerName, array $fields = array())
    {
        $this->name        = $name;
        $this->managerName = $managerName;
        $this->fields      = $fields;
    }

    public function setRepositoryMethod($repositoryMethod)
    {
        $this->repositoryMethod = $repositoryMethod;
    }

    public function getRepositoryMethod()
    {
        return $this->repositoryMethod;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getManagerName()
    {
        return $this->managerName;
    }

    public function getFields()
    {
        return $this->fields;
    }
}