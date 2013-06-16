<?php

namespace Pok\PoolDBM\Console\Helper;

use Symfony\Component\Console\Helper\Helper;

use Twig_Loader_Filesystem;
use Twig_Environment;

class TemplateEngineHelper extends Helper implements TemplateHelperInterface
{
    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * Constructor.
     *
     * @param string|array $paths   A path or an array of paths where to look for templates
     * @param array        $options An array of options environment
     */
    public function __construct($paths, array $options = array())
    {
        $this->twig = new Twig_Environment(new Twig_Loader_Filesystem($paths), $options);
    }

    /**
     * {@inheritdoc}
     */
    public function render($name, array $context = array())
    {
        return $this->twig->render($name, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'template';
    }
}
