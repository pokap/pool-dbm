<?php

namespace Pok\PoolDBM\Tests\Fixtures\Test1;

use Pok\PoolDBM\Tests\Mocks\ObjectManagerMock;

class DocumentManager extends ObjectManagerMock
{
    public function getRepository($className)
    {
        return new DocumentRepository();
    }
}
