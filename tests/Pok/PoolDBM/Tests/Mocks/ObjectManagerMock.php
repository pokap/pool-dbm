<?php

namespace Pok\PoolDBM\Tests\Mocks;

use Doctrine\Common\Persistence\ObjectManager;

class ObjectManagerMock extends stdClassMock implements ObjectManager
{
    public function getRepository($className) {}
    public function persist($object) {}
    public function remove($object) {}
    public function flush($object = null) {}
    public function clear($object = null) {}
    public function find($className, $id) {}
    public function merge($object) {}
    public function detach($object) {}
    public function refresh($object) {}

    public function getClassMetadata($className)
    {
        $meta = new ClassMetadataMock();
        $meta->className = $className;

        return $meta;
    }

    public function getMetadataFactory() {}
    public function initializeObject($obj) {}
    public function contains($object) {}
}
