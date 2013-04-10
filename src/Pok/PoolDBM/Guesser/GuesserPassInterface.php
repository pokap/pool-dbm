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
     * Returns small list of managers by priority.
     *
     * @param \ArrayIterator $managers
     *
     * @return \ArrayIterator
     */
    function process(\ArrayIterator $managers);
}