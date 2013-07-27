<?php

namespace MultiModel;

class User
{
    /**
     * @var \Entity\User
     */
    private $entity;

    /**
     * @var \Document\User
     */
    private $document;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->document->getId();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->entity->getName();
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->entity->setName($name);
    }

    /**
     * @return string
     */
    public function getProfileContent()
    {
        return $this->document->getProfileContent();
    }

    /**
     * @param string $profileContent
     */
    public function setProfileContent($profileContent)
    {
        $this->document->setProfileContent($profileContent);
    }
}
