<?php

namespace Pok\PoolDBM\Manager;

use Doctrine\Common\Persistence\ObjectManager;

class Pool implements PoolInterface
{
    private $managers;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->managers = new \ArrayObject();
    }

    /**
     * {@inheritdoc}
     */
    public function addManager($name, ObjectManager $manager)
    {
        $this->managers[$name] = $manager;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasManager($name)
    {
        return isset($this->managers[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager($name)
    {
        return $this->managers[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->managers->getIterator();
    }
}