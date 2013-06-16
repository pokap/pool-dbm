<?php

namespace Pok\PoolDBM\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/** @Annotation */
final class MultiModel extends Annotation
{
    public $model;
    public $repositoryClass;
}
