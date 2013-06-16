<?php

namespace Pok\PoolDBM\Tests\Fixtures\Test2\Entity;

class UserRepository
{
    public static $count = 0;

    public function findOneBy($criteria)
    {
        $entities = $this->findBy($criteria);

        return $entities[$criteria['id'] - 1];
    }

    public function findByIds(array $ids)
    {
        self::$count++;

        $list = array();
        foreach ($ids as $id) {
            $entity = new User;
            $entity->id = $id;

            $list[] = $entity;
        }

        return $list;
    }

    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        self::$count++;

        $list = array();
        foreach (range(1, 2) as $id) {
            $entity = new User;
            $entity->id = $id;

            $list[] = $entity;
        }

        $group1 = new Group;
        $group1->id = 1;
        $group1->documentId = 5;
        $group1->users[] = $list[0];
        $group1->users[] = $list[1];

        $group2 = new Group;
        $group2->id = 2;
        $group2->documentId = 5;
        $group2->users[] = $list[0];

        $list[0]->groups[] = $group1;
        $list[0]->groups[] = $group2;

        $list[1]->groups[] = $group1;

        return $list;
    }
}
