<?php

namespace Pok\PoolDBM\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Pok\PoolDBM\Guesser\GuesserPassInterface;

interface PoolInterface extends \IteratorAggregate
{
    /**
     * @param string        $name
     * @param ObjectManager $provider
     *
     * @return PoolInterface
     */
    public function addManager($name, ObjectManager $provider);

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function hasManager($name);

    /**
     * @param string $name
     *
     * @return ObjectManager
     */
    public function getManager($name);

    /**
     * Process guesser pass with pool managers.
     *
     * @param GuesserPassInterface $guesser
     */
    public function registerGuesserPass(GuesserPassInterface $guesser);

    /**
     * @param $name
     *
     * @return boolean
     */
    public function hasPriority($name);

    /**
     * @param string $name
     *
     * @return array
     */
    public function getPriority($name);
}
