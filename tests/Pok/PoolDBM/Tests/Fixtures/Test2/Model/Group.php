<?php

namespace Pok\PoolDBM\Tests\Fixtures\Test2\Model;

use Pok\PoolDBM\Collections\ArrayCollection;
use Pok\PoolDBM\Tests\Fixtures\Test2\Entity\Group as EntityGroup;
use Pok\PoolDBM\Tests\Fixtures\Test2\Document\Group as DocumentGroup;

class Group
{
    public $entity;
    public $document;
    public $users;

    public function __construct()
    {
        $this->entity   = new EntityGroup();
        $this->document = new DocumentGroup();

        $this->users = new ArrayCollection($this, array(
            'entity' => 'getUsers'
        ));
    }

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

    public function getId()
    {
        return $this->entity->id;
    }

    public function setId($id)
    {
        $this->entity->id = $id;
        $this->document->id = $id;
    }

    public function getUsers()
    {
        return $this->users;
    }
}
