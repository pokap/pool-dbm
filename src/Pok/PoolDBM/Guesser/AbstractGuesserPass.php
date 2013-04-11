<?php

namespace Pok\PoolDBM\Guesser;

/**
 * AbstractGuesserPass
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
abstract class AbstractGuesserPass implements GuesserPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(\ArrayIterator $managers)
    {
        $priority = array();

        foreach ($managers as $name => $manager) {
            if ($this->guess($name, $manager)) {
                $priority[] = $name;
            }
        }

        return $priority;
    }
}
