<?php

namespace Pok\PoolDBM\Tests\Fixtures\Test2\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class Group
{
    public $id;
    public $documentId;
    public $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getDocumentId()
    {
        return $this->documentId;
    }

    public function getUsers()
    {
        return $this->users;
    }
}
