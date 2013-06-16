<?php

namespace TestMultiModel;

class User
{
    protected $entity;
    protected $document;

    public function setEntity($entity)
    {
        $this->entity = $entity;
    }
    public function getEntity()
    {
        return $this->entity;
    }
    public function setDocument($document)
    {
        $this->document = $document;
    }
    public function getDocument()
    {
        return $this->document;
    }
}
