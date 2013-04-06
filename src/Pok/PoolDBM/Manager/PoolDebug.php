<?php

namespace Pok\PoolDBM\Manager;

use Doctrine\Common\Persistence\ObjectManager;

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
    function getManager($name)
    {
        if (!$this->hasManager($name)) {
            throw new \RuntimeException(sprintf('The manager name "%s" does not exists.', $name));
        }

        return parent::getManager($name);
    }
}