<?php

namespace Pok\PoolDBM;

use Doctrine\Common\Collections\Collection;

/**
 * PersistentCollection
 * The principe is that already exists system of persistent collection with manager referent.
 * He leans over and hydrate the data from its collection.
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
class PersistentCollection implements Collection
{
    /**
     * @var Collection
     */
    private $coll;

    /**
     * @param ModelManager $manager
     */
    private $manager;

    /**
     * @var boolean
     */
    private $initialized;

    /**
     * Constructor.
     *
     * @param object $model Model instance
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($model)
    {
        if (!is_object($model)) {
            throw new \InvalidArgumentException(sprintf('Argument 1 passed must be a model instance, %s given.', gettype($model)));
        }

        $this->model       = $model;
        $this->initialized = false;
    }

    /**
     * @param ModelManager $manager
     *
     * @throws \RuntimeException
     */
    public function setModelManager(ModelManager $manager)
    {
        $class = $manager->getClassMetadata(get_class($this->model));

        $this->manager = $manager;
        $this->coll    = $this->model->{'get'.ucfirst($class->getManagerIdentifier())}();

        if (!$this->coll instanceof Collection) {
            throw new \RuntimeException(sprintf('Method %s::get%s() must return instance of \\Doctrine\\Common\\Collections\\Collection.', get_class($this->model), ucfirst($class->getManagerIdentifier())));
        }
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    public function initialize()
    {
        if (null === $this->manager) {
            throw new \RuntimeException('Manger must be set.');
        }

        if ($this->initialized) {
            return;
        }

        $this->initialized = true;

        $this->coll->clear();
        $this->manager->getUnitOfWork()->loadCollection($this);

        // Reattach NEW objects added through add(), if any.
        if (isset($newObjects)) {
            foreach ($newObjects as $key => $obj) {
                if ($this->mapping['strategy'] === 'set') {
                    $this->coll->set($key, $obj);
                } else {
                    $this->coll->add($obj);
                }
            }
            $this->isDirty = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add($element)
    {
        return $this->coll->add($element);
    }

    /**
     * {@inheritdoc}
     */
    function clear()
    {
        if ($this->initialized && $this->isEmpty()) {
            return;
        }

        $this->coll->clear();
    }

    /**
     * {@inheritdoc}
     */
    function contains($element)
    {
        $this->initialize();

        return $this->coll->contains($element);
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return 0 === $this->coll->count();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        $this->initialize();

        if ($removed = $this->coll->remove($key)) {
            // @todo remove all sub-element (manager) in model
        }

        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function removeElement($element)
    {
        $this->initialize();

        if ($removed = $this->coll->removeElement($element)) {
            // @todo remove all sub-element (manager) in model
        }

        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function containsKey($key)
    {
        $this->initialize();

        return $this->coll->containsKey($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $this->initialize();

        return $this->coll->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys()
    {
        $this->initialize();

        return $this->coll->getKeys();
    }

    /**
     * {@inheritdoc}
     */
    public function getValues()
    {
        $this->initialize();

        return $this->coll->getValues();
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->coll->set($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $this->initialize();
        return $this->coll->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function first()
    {
        $this->initialize();

        return $this->coll->first();
    }

    /**
     * {@inheritdoc}
     */
    function last()
    {
        $this->initialize();

        return $this->coll->last();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->coll->key();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->coll->current();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        return $this->coll->next();
    }

    /**
     * {@inheritdoc}
     */
    public function exists(\Closure $p)
    {
        $this->initialize();

        return $this->coll->exists($p);
    }

    /**
     * {@inheritdoc}
     */
    public function filter(\Closure $p)
    {
        $this->initialize();

        return $this->coll->filter($p);
    }

    /**
     * {@inheritdoc}
     */
    public function forAll(\Closure $p)
    {
        $this->initialize();

        return $this->coll->forAll($p);
    }

    /**
     * {@inheritdoc}
     */
    public function map(\Closure $func)
    {
        $this->initialize();

        return $this->coll->map($func);
    }

    /**
     * {@inheritdoc}
     */
    public function partition(\Closure $p)
    {
        $this->initialize();

        return $this->coll->partition($p);
    }

    /**
     * {@inheritdoc}
     */
    public function indexOf($element)
    {
        $this->initialize();

        return $this->coll->indexOf($element);
    }

    /**
     * {@inheritdoc}
     */
    public function slice($offset, $length = null)
    {
        $this->initialize();

        return $this->coll->slice($offset, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $this->initialize();

        return $this->coll->getIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->containsKey($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if (!isset($offset)) {
            return $this->add($value);
        }

        $this->set($offset, $value);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        return $this->remove($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->coll->count();
    }
}