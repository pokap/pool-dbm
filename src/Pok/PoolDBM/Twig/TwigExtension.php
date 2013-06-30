<?php

namespace Pok\PoolDBM\Twig;

/**
 * Twig extension for tpl generator model.
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
class TwigExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            'ucfirst' => new \Twig_Filter_Function('ucfirst'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pool_dbm';
    }
}
