<?php

namespace Pok\PoolDBM\Tests\Fixtures\Test2\Entity;

class GroupRepository
{
    public static $count = 0;

    public function find($id)
    {
        $list = $this->findByIds(array($id));

        return $list[$id - 1];
    }

    public function findOneBy($criteria)
    {
        return $this->find($criteria['id']);
    }

    public function findByIds(array $ids)
    {
        self::$count++;

        $list = array();
        foreach ($ids as $id) {
            $entity = new Group;
            $entity->id = $id;
            $entity->documentId = 5;

            $list[] = $entity;
        }

        $user1 = new User();
        $user1->id = 1;

        $user2 = new User();
        $user2->id = 2;

        $list[0]->users[] = $user1;
        $list[0]->users[] = $user2;

        $list[1]->users[] = $user1;

        return $list;
    }
}
