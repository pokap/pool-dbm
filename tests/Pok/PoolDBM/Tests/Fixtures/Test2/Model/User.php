<?php

namespace Pok\PoolDBM\Tests\Fixtures\Test2\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Pok\PoolDBM\Tests\Fixtures\Test2\Entity\User as EntityUser;
use Pok\PoolDBM\Tests\Fixtures\Test2\Document\User as DocumentUser;

class User
{
    public $entity;
    public $document;
    public $groups;

    public function __construct()
    {
        $this->entity   = new EntityUser();
        $this->document = new DocumentUser();

        $this->groups = new ArrayCollection();
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

    public function setGroups(array $groups)
    {
        foreach ($groups as $group) {
            $this->groups->add($group);
        }
    }
}
