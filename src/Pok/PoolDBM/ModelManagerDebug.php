<?php

namespace Pok\PoolDBM;

/**
 * Class ModelManager in debug mode.
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
class ModelManagerDebug extends ModelManager
{
    /**
     * {@inheritDoc}
     */
    public function getClassMetadata($className)
    {
        $class = parent::getClassMetadata($className);

        if (!$class) {
            throw new \RuntimeException(sprintf('Model "%s" in not register in class metadata.', $className));
        }

        return $class;
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder($className, $alias)
    {
        $repository = $this->getIdentifierRepository($className);

        if (!method_exists($repository, 'createQueryBuilder')) {
            throw new \BadMethodCallException(sprintf('The repository "%s" of manager for class "%s" not implement createQueryBuilder.', get_class($repository), $className));
        }

        return $repository->createQueryBuilder($alias);
    }

    /**
     * {@inheritDoc}
     */
    public function persist($model)
    {
        self::throwIfInvalidModelArgumentException($model);

        parent::persist($model);
    }

    /**
     * {@inheritDoc}
     */
    public function remove($model)
    {
        self::throwIfInvalidModelArgumentException($model);

        parent::remove($model);
    }

    /**
     * {@inheritDoc}
     */
    public function refresh($model)
    {
        self::throwIfInvalidModelArgumentException($model);

        parent::refresh($model);
    }

    /**
     * {@inheritDoc}
     */
    public function detach($model)
    {
        self::throwIfInvalidModelArgumentException($model);

        parent::detach($model);
    }

    /**
     * {@inheritDoc}
     */
    public function merge($model)
    {
        self::throwIfInvalidModelArgumentException($model);

        return parent::merge($model);
    }

    /**
     * {@inheritDoc}
     */
    public function flush($model = null, array $options = array())
    {
        if (null !== $model && !is_array($model)) {
            self::throwIfInvalidModelArgumentException($model);
        }

        parent::flush($model, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($model)
    {
        self::throwIfInvalidModelArgumentException($model);

        return parent::contains($model);
    }

    /**
     * {@inheritdoc}
     */
    public function transactional($func)
    {
        if (!is_callable($func)) {
            throw new \InvalidArgumentException('Expected argument of type "callable", got "' . gettype($func) . '"');
        }

        return parent::transactional($func);
    }

    /**
     * @param mixed $model
     *
     * @throws \InvalidArgumentException
     */
    protected static function throwIfInvalidModelArgumentException($model)
    {
        if (!is_object($model)) {
            throw new \InvalidArgumentException(sprintf('You must flush a object model, you given type "%s".', gettype($model)));
        }
    }
}