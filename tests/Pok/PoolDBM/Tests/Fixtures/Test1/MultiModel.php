<?php

namespace Pok\PoolDBM\Tests\Fixtures\Test1;

use Doctrine\Common\Collections\ArrayCollection;

class MultiModel
{
    public $entity;
    public $document;

    public $parent;
    public $childrens;

    public function __construct()
    {
        $this->entity = new Entity();
        $this->document = new Document();

        $this->childrens = new ArrayCollection();
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    public function setDocument($document)
    {
        $this->document = $document;
    }

    public function getId()
    {
        return $this->entity->id;
    }

    public function setId($id)
    {
        $this->entity->id = $id;
        $this->document->id = $id;
    }

    public function setParent(MultiModel $model)
    {
        $this->parent = $model;
    }

    public function setChildrens(array $childrens)
    {
        foreach ($childrens as $children) {
            $this->childrens->add($children);
        }
    }
}
