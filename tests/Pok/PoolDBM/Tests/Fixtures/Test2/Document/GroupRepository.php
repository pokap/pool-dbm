<?php

namespace Pok\PoolDBM\Tests\Fixtures\Test2\Document;

class GroupRepository
{
    public static $count = 0;

    public function find($id)
    {
        self::$count++;

        if ($id != 5) {
            return null;
        }

        $document = new Group;
        $document->id = $id;

        return $document;
    }

    public function findByIds(array $ids)
    {
        self::$count++;

        $document = new Group;
        $document->id = 5;

        return array($document);
    }
}
