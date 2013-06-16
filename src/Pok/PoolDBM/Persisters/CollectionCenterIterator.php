<?php

namespace Pok\PoolDBM\Persisters;

class CollectionCenterIterator implements \Iterator, \Countable
{
    /**
     * @var CollectionCenter[]
     */
    private $collections;

    /**
     * @var array
     */
    private $cursorClassName;

    /**
     * @var integer
     */
    private $position;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->clean();
    }

    /**
     * @param CollectionCenter $collection
     */
    public function append(CollectionCenter $collection)
    {
        $this->cursorClassName[] = $collection->getClassName();
        $this->collections[]     = $collection;
    }

    /**
     * @param string $className
     * @param string $field
     *
     * @return CollectionCenter
     *
     * @throws \InvalidArgumentException
     */
    public function get($className, $field)
    {
        foreach ($this->cursorClassName as $key => $cursor) {
            if ($cursor === $className && $field === $this->collections[$key]->getField()) {
                return $this->collections[$key];
            }
        }

        throw new \InvalidArgumentException(sprintf('Collection "%s" with "%s" field have not be apppend.', $className, $field));
    }

    /**
     * @param string $className
     *
     * @return CollectionCenter[]
     */
    public function getAll($className)
    {
        $collections = array();
        foreach ($this->cursorClassName as $key => $cursor) {
            if ($cursor === $className) {
                $collections[] = $this->collections[$key];
            }
        }

        return $collections;
    }

    /**
     * {@inheritdoc}
     *
     * @return CollectionCenter
     */
    public function current()
    {
        return $this->collections[$this->position];
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function key()
    {
        return $this->cursorClassName[$this->position];
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return isset($this->collections[$this->position]);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->collections);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->collections);
    }

    /**
     * Clean list.
     */
    public function clean()
    {
        $this->collections     = array();
        $this->cursorClassName = array();
        $this->position        = 0;
    }
}
