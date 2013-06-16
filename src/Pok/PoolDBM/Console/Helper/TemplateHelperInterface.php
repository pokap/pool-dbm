<?php

namespace Pok\PoolDBM\Console\Helper;

/**
 * TemplateHelperInterface
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
interface TemplateHelperInterface
{
    /**
     * Renders a template.
     *
     * @param string $name    Template name
     * @param array  $context Parameters to pass to the template
     *
     * @return string
     */
    public function render($name, array $context = array());
}
