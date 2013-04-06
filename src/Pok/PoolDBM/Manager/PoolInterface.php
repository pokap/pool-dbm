<?php

namespace Pok\PoolDBM\Manager;

use Doctrine\Common\Persistence\ObjectManager;

interface PoolInterface extends \IteratorAggregate
{
    /**
     * @param string        $name
     * @param ObjectManager $provider
     *
     * @return PoolInterface
     */
    function addManager($name, ObjectManager $provider);

    /**
     * @param string $name
     *
     * @return boolean
     */
    function hasManager($name);

    /**
     * @param string $name
     *
     * @return ObjectManager
     */
    function getManager($name);
}