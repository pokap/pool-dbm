<?php

namespace Pok\PoolDBM\Tests\Fixtures\Test1;

use Doctrine\Common\Collections\ArrayCollection;

class Entity
{
    public $id;
    public $parent;
    public $childrens;

    public function __construct()
    {
        $this->childrens = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getChildrens()
    {
        return $this->childrens;
    }
}
