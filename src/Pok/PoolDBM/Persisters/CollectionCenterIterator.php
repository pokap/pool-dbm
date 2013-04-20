<?php

namespace Pok\PoolDBM\Persisters;

class CollectionCenterIterator implements \Iterator
{
    /**
     * @var CollectionCenter[]
     */
    private $collections;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->collections = array();
    }

    /**
     * @param CollectionCenter $collection
     */
    public function append(CollectionCenter $collection)
    {
        $this->collections[$collection->getClassName()] = $collection;
    }

    /**
     * @param string $className
     *
     * @return CollectionCenter
     */
    public function get($className)
    {
        return $this->collections[$className];
    }

    /**
     * {@inheritdoc}
     *
     * @return CollectionCenter
     */
    public function current()
    {
        return current($this->collections);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        next($this->collections);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function key()
    {
        return key($this->collections);
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return null !== $this->key();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        reset($this->collections);
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->collections);
    }

    /**
     * Clean list.
     */
    public function clean()
    {
        $this->collections = array();
    }
}
