<?php

namespace Pok\PoolDBM\Collections;

use Doctrine\Common\Collections\ArrayCollection as CommonArrayCollection;

/**
 * ArrayCollection
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
class ArrayCollection extends CommonArrayCollection
{
    /**
     * @var object
     */
    protected $model;

    /**
     * @var array
     */
    protected $fields;

    /**
     * Constructor.
     *
     * Field must to be build like [manager name] => [method]
     *
     * Example:
     *  $this->users = new ArrayCollection($this, array(
     *    'entity'   => 'getUser',
     *    'document' => 'getUser'
     *  ));
     *
     * @param object $model
     * @param array  $fields
     * @param array  $elements (optional)
     */
    public function __construct($model, array $fields, array $elements = array())
    {
        $this->model = $model;

        foreach ($fields as $managerName => $method) {
            $this->fields[sprintf('get%s', ucfirst($managerName))] = $method;
        }

        foreach ($elements as $element) {
            $this->add($element);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add($element)
    {
        foreach ($this->fields as $managerName => $method) {
            call_user_func(
                array(self::getElementMethod($this->model, $managerName, $method), 'add'),
                call_user_func(array($element, $managerName))
            );
        }

        return parent::add($element);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        foreach ($this->fields as $managerName => $method) {
            call_user_func(array(self::getElementMethod($this->model, $managerName, $method), 'remove'), $key);
        }

        return parent::remove($key);
    }

    /**
     * {@inheritdoc}
     */
    public function removeElement($element)
    {
        foreach ($this->fields as $managerName => $method) {
            call_user_func(
                array(self::getElementMethod($this->model, $managerName, $method), 'removeElement'),
                call_user_func(array($element, $managerName))
            );
        }

        return parent::removeElement($element);
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        foreach ($this->fields as $managerName => $method) {
            call_user_func(
                array(self::getElementMethod($this->model, $managerName, $method), 'set'),
                $key, call_user_func(array($value, $managerName))
            );
        }

        parent::set($key, $value);
    }

    /**
     * @param object $model
     * @param string $managerName
     * @param string $method
     *
     * @return mixed
     */
    protected static function getElementMethod($model, $managerName, $method)
    {
        return call_user_func(array(call_user_func(array($model, $managerName)), $method));
    }
}
