<?php

namespace MultiModel;

class Test
{
    private $entity;

    private $document;

    public function getId()
    {
        return $this->document->getId();
    }

    public function getName()
    {
        return $this->entity->getName();
    }

    public function setName($name)
    {
        $this->entity->setName($name);
    }

    public function getProfileContent()
    {
        return $this->document->getProfileContent();
    }

    public function setProfileContent($profileContent)
    {
        $this->document->setProfileContent($profileContent);
    }
}
