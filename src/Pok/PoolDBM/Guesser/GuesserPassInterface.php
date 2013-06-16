<?php

namespace Pok\PoolDBM\Guesser;

/**
 * Pass guess process after that pool is filled.
 * Principe is that there are many rules for sort managers according to the needs.
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
interface GuesserPassInterface
{
    /**
     * Returns small list of manager name by priority.
     *
     * @param \ArrayIterator $managers
     *
     * @return array
     */
    public function process(\ArrayIterator $managers);

    /**
     * Returns if manager can add to priority list managers.
     *
     * @param string $name
     * @param object $manager
     *
     * @return boolean
     */
    public function guess($name, $manager);

    /**
     * Returns name of guesser pass.
     *
     * @return string
     */
    public function getName();
}
