<?php

namespace Pok\PoolDBM\Tests\Fixtures\Test2;

use Pok\PoolDBM\Tests\Fixtures\Test2\Entity\UserRepository;
use Pok\PoolDBM\Tests\Fixtures\Test2\Entity\GroupRepository;
use Pok\PoolDBM\Tests\Mocks\ObjectManagerMock;

class EntityManager extends ObjectManagerMock
{
    public function getRepository($className)
    {
        if ('\Group' == strrchr($className, '\\')) {
            return new GroupRepository();
        }

        return new UserRepository();
    }
}
