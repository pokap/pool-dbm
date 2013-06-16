<?php

namespace Pok\PoolDBM\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Pok\PoolDBM\Guesser\GuesserPassInterface;

class PoolDebug extends Pool
{
    /**
     * {@inheritdoc}
     */
    public function addManager($name, ObjectManager $manager)
    {
        if ($this->hasManager($name)) {
            throw new \RuntimeException(sprintf('The manager name "%s" is already used.', $name));
        }

        return parent::addManager($name, $manager);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager($name)
    {
        if (!$this->hasManager($name)) {
            throw new \RuntimeException(sprintf('The manager name "%s" does not exists.', $name));
        }

        return parent::getManager($name);
    }

    /**
     * {@inheritdoc}
     */
    public function registerGuesserPass(GuesserPassInterface $guesser)
    {
        if ($this->hasPriority($guesser->getName())) {
            throw new \RuntimeException(sprintf('The guesser pass "%s" already registered.', $guesser->getName()));
        }

        parent::registerGuesserPass($guesser);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority($name)
    {
        if (!$this->hasPriority($name)) {
            throw new \RuntimeException(sprintf('The priority managers name "%s" does not exists.', $name));
        }

        return parent::getPriority($name);
    }
}
