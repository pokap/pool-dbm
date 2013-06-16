<?php

namespace Pok\PoolDBM\Tests\Fixtures\Test2\Document;

class UserRepository
{
    public static $count = 0;

    public function find($id)
    {
        self::$count++;

        $document = new User;
        $document->id = $id;

        return $document;
    }

    public function findByIds(array $ids)
    {
        self::$count++;

        $list = array();
        foreach ($ids as $id) {
            $document = new User;
            $document->id = $id;

            $list[] = $document;
        }

        return $list;
    }
}
