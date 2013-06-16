<?php

namespace Pok\PoolDBM\Tests\Fixtures\Test2;

use Pok\PoolDBM\Tests\Fixtures\Test2\Document\UserRepository;
use Pok\PoolDBM\Tests\Fixtures\Test2\Document\GroupRepository;
use Pok\PoolDBM\Tests\Mocks\ObjectManagerMock;

class DocumentManager extends ObjectManagerMock
{
    public function getRepository($className)
    {
        if ('\Group' == strrchr($className, '\\')) {
            return new GroupRepository();
        }

        return new UserRepository();
    }
}
