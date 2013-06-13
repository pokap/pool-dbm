<?php

namespace Pok\PoolDBM;

use Doctrine\Common\Collections\ArrayCollection;
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
     * @var ArrayCollection
     */
    protected $coll;

    /**
     * @var ModelManager
     */
    protected $manager;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var object
     */
    protected $model;

    /**
     * @var \Pok\PoolDBM\Mapping\ClassMetadata
     */
    protected $metadata;

    /**
     * Constructor.
     *
     * @param object $model     Model instance
     * @parma string $fieldName
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($model, $fieldName)
    {
        if (!is_object($model)) {
            throw new \InvalidArgumentException(sprintf('Argument 1 passed must be a model instance, %s given.', gettype($model)));
        }

        $this->fieldName = $fieldName;
        $this->model     = $model;
        $this->coll      = new ArrayCollection();
    }

    /**
     * @param ModelManager $manager
     *
     * @throws \RuntimeException
     */
    public function setModelManager(ModelManager $manager)
    {
        $this->metadata = $manager->getClassMetadata(get_class($this->model));
        $this->manager  = $manager;

        if (!$this->coll instanceof Collection) {
            throw new \RuntimeException(sprintf('Method %s::get%s() must return instance of \\Doctrine\\Common\\Collections\\Collection.', get_class($this->model), ucfirst($class->getManagerIdentifier())));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add($element)
    {
        $this->exec($element, 'add');

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        if ($this->isEmpty()) {
            return;
        }

        $this->coll->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function contains($element)
    {
        return $this->coll->contains($element);
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return $this->coll->isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        return $this->exec($key, 'remove');
    }

    /**
     * {@inheritdoc}
     */
    public function removeElement($element)
    {
        return $this->exec($element, 'removeElement');
    }

    /**
     * {@inheritdoc}
     */
    public function containsKey($key)
    {
        return $this->coll->containsKey($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return $this->coll->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys()
    {
        return $this->coll->getKeys();
    }

    /**
     * {@inheritdoc}
     */
    public function getValues()
    {
        return $this->coll->getValues();
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        return $this->exec($value, 'set', $key);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->coll->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function first()
    {
        return $this->coll->first();
    }

    /**
     * {@inheritdoc}
     */
    public function last()
    {
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
        return $this->coll->exists($p);
    }

    /**
     * {@inheritdoc}
     */
    public function filter(\Closure $p)
    {
        return $this->coll->filter($p);
    }

    /**
     * {@inheritdoc}
     */
    public function forAll(\Closure $p)
    {
        return $this->coll->forAll($p);
    }

    /**
     * {@inheritdoc}
     */
    public function map(\Closure $func)
    {
        return $this->coll->map($func);
    }

    /**
     * {@inheritdoc}
     */
    public function partition(\Closure $p)
    {
        return $this->coll->partition($p);
    }

    /**
     * {@inheritdoc}
     */
    public function indexOf($element)
    {
        return $this->coll->indexOf($element);
    }

    /**
     * {@inheritdoc}
     */
    public function slice($offset, $length = null)
    {
        return $this->coll->slice($offset, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
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

    /**
     * Exec function with element like parameter.
     *
     * @param mixed      $element
     * @param string     $func
     * @param mixed|null $key     (optional)
     *
     * @return mixed
     */
    protected function exec($element, $func, $key = NULL)
    {
        $assoc  = $this->metadata->getAssociationDefinition($this->fieldName);

        foreach ($assoc->getManagerNames() as $managerName) {
            $coll = $this->model
                ->{'get'.ucfirst($managerName)}()
                ->{'get'.ucfirst($assoc->getFieldNameByManagerName($managerName))};

            if ($key) {
                call_user_func_array(array($coll, $func), $key, $element->{'get' . ucfirst($managerName)}());
            } else {
                call_user_func_array(array($coll, $func), $element->{'get' . ucfirst($managerName)}());
            }
        }

        if ($key) {
            return call_user_func(array($this->coll, $func), $key, $element);
        } else {
            return call_user_func(array($this->coll, $func), $element);
        }
    }
}
