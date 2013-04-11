<?php

namespace Pok\PoolDBM\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Pok\PoolDBM\Guesser\GuesserPassInterface;

class Pool implements PoolInterface
{
    /**
     * @var \ArrayObject
     */
    private $managers;

    /**
     * @var \ArrayObject
     */
    private $priorities;

    /**
     * @var GuesserPassInterface[]
     */
    private $guessers;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->managers   = new \ArrayObject();
        $this->priorities = new \ArrayObject();
        $this->guessers   = array();
    }

    /**
     * {@inheritdoc}
     */
    public function addManager($name, ObjectManager $manager)
    {
        $this->managers[$name] = $manager;

        foreach ($this->guessers as $guesser) {
            if ($guesser->guess($name, $manager)) {
                $this->priorities[$guesser->getName()] = $name;
            }
        }

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
    public function registerGuesserPass(GuesserPassInterface $guesser)
    {
        $this->guessers[] = $guesser;
        $this->priorities[$guesser->getName()] = $guesser->process($this->getIterator());
    }

    /**
     * {@inheritdoc}
     */
    public function hasPriority($name)
    {
        return isset($this->priorities[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority($name)
    {
        return $this->priorities[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->managers->getIterator();
    }
}
