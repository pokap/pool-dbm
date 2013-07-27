<?php

namespace Document;

class User
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $profileContent;

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $profileContent
     */
    public function setProfileContent($profileContent)
    {
        $this->profileContent = $profileContent;
    }

    /**
     * @return string
     */
    public function getProfileContent()
    {
        return $this->profileContent;
    }
}
