<?php

namespace Pok\PoolDBM\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/** @Annotation */
final class ModelReference extends Annotation
{
    public $manager;
    public $field;
}
