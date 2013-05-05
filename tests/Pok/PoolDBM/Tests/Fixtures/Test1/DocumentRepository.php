<?php

namespace Pok\PoolDBM\Tests\Fixtures\Test1;

class DocumentRepository
{
    public static $count = 0;

    public function find($id)
    {
        self::$count++;

        $document = new Document;
        $document->id = $id;

        return $document;
    }

    public function findByIds(array $ids)
    {
        self::$count++;

        $list = array();
        foreach ($ids as $id) {
            $document = new Document;
            $document->id = $id;

            $list[] = $document;
        }

        return $list;
    }
}
