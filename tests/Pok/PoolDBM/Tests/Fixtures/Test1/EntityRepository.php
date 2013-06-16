<?php

namespace Pok\PoolDBM\Tests\Fixtures\Test1;

class EntityRepository
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
            $entity = new Entity;
            $entity->id = $id;

            $list[] = $entity;
        }

        return $list;
    }

    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        self::$count++;

        $list = array();
        foreach (range(1, 5) as $id) {
            $entity = new Entity;
            $entity->id = $id;

            $list[] = $entity;
        }

        // 5 is a children of 3
        $list[4]->parent = $list[2];
        $list[0]->parent = $list[2];
        $list[2]->childrens[] = $list[4];
        $list[2]->childrens[] = $list[0];

        return $list;
    }
}
