<?php

namespace Pok\PoolDBM\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver as AbstractAnnotationDriver;
use Doctrine\Common\Annotations\Reader;

use Pok\PoolDBM\Mapping\Annotations as ODM;
use Pok\PoolDBM\Mapping\ClassMetadataInfo;

/**
 * AnnotationDriver.
 *
 * @author Florent Denis <dflorent.pokap@gmail.com>
 */
class AnnotationDriver extends AbstractAnnotationDriver
{
    /**
     * Registers annotation classes to the common registry.
     *
     * This method should be called when bootstrapping your application.
     */
    public static function registerAnnotationClasses()
    {
        AnnotationRegistry::registerFile(__DIR__ . '/../Annotations/DoctrineAnnotations.php');
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        /** @var $class ClassMetadataInfo */
        $reflClass = $class->getReflectionClass();

        foreach ($this->reader->getClassAnnotations($reflClass) as $annot) {
            if ($annot instanceof ODM\MultiModel) {
                if (!empty($annot->repositoryClass)) {
                    $class->setCustomRepositoryClass($annot->repositoryClass);
                }
            } elseif ($annot instanceof ODM\ModelReference) {
                $class->setIdentifier($annot->manager, $annot->field);
            }
        }

        foreach ($reflClass->getProperties() as $property) {
            foreach ($this->reader->getPropertyAnnotations($property) as $annot) {
                if ($annot instanceof ODM\Model) {
                    $class->addModel($annot->manager, $annot->name, array(),  $annot->repositoryMethod);
                }
            }
        }
    }

    /**
     * Factory method for the Annotation Driver
     *
     * @param array|string $paths
     * @param Reader $reader
     * @return AnnotationDriver
     */
    static public function create($paths = array(), Reader $reader = null)
    {
        if ($reader == null) {
            $reader = new AnnotationReader();
        }
        return new self($reader, $paths);
    }
}