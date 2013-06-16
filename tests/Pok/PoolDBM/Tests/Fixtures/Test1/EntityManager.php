<?php

namespace Pok\PoolDBM\Tests\Fixtures\Test1;

use Pok\PoolDBM\Tests\Mocks\ObjectManagerMock;

class EntityManager extends ObjectManagerMock
{
    public function getRepository($className)
    {
        return new EntityRepository();
    }
}
