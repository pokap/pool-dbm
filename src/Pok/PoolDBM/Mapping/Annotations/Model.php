<?php

namespace Pok\PoolDBM\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/** @Annotation */
final class Model extends Annotation
{
    public $name;
    public $manager;
    public $repositoryMethod;
}
