<?php

namespace Pok\PoolDBM\Persisters;

class CollectionCenterData
{
    /**
     * @var CollectionCenter;
     */
    protected $collection;

    /**
     * @var string
     */
    protected $datas;

    /**
     * Constructor.
     *
     * @param CollectionCenter $collectionCenter
     */
    public function __construct(CollectionCenter $collectionCenter)
    {
        $this->collectionCenter  = $collectionCenter;

        $this->datas = array();
    }

    /**
     * @return CollectionCenter
     */
    public function getCollectionCenter()
    {
        return $this->collectionCenter;
    }

    /**
     * @param string $managerName
     * @param object $data
     */
    public function addData($managerName, $data)
    {
        $this->datas[$managerName] = $data;
    }

    /**
     * @return array
     */
    public function getDatas()
    {
        return $this->datas;
    }

    /**
     * @return array
     */
    public function getFirstData()
    {
        reset($this->datas);

        return array(key($this->datas) => current($this->datas));
    }
}
