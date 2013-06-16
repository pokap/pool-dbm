<?php

namespace Pok\PoolDBM\Tests\Fixtures\Test2\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class User
{
    public $id;
    public $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getGroups()
    {
        return $this->groups;
    }
}
