<?php

namespace Pok\PoolDBM\Util;

/**
 * Class Bag.
 * Used by console command.
 */
class Bag
{
    public $one;
    public $many;

    /**
     * Constructor.
     *
     * @param array $one
     * @param array $many
     */
    public function __construct(array $one = array(), array $many = array())
    {
        $this->one  = $one;
        $this->many = $many;
    }

    /**
     * Returns all associations
     *
     * @return array
     */
    public function getAll()
    {
        return array_merge($this->one, $this->many);
    }
}
