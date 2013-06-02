<?php

namespace Pok\PoolDBM\Console\Helper;

use Symfony\Component\Console\Helper\Helper;

use Pok\PoolDBM\ModelManager;

/**
 * ModelManagerHelper
 */
class ModelManagerHelper extends Helper
{
    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * Constructor.
     *
     * @param ModelManager $modelManager Model manager
     */
    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    /**
     * Retrieves Model manager.
     *
     * @return ModelManager
     */
    public function getEntityManager()
    {
        return $this->modelManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'modelManager';
    }
}
