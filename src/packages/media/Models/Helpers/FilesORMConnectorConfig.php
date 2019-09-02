<?php


namespace Arrow\Media\Models\Helpers;


class FilesORMConnectorConfig
{

    const CONN_SINGLE = 1;
    const CONN_MULTI = 2;

    protected $allowedExtensions = [];
    protected $onlyImages = false;
    protected $maxSize = -1;
    protected $connectionType = self::CONN_MULTI;



    /**
     * @return array
     */
    public function getAllowedExtensions()
    {
        return $this->allowedExtensions;
    }

    /**
     * @param array $allowedExtensions
     */
    public function setAllowedExtensions($allowedExtensions)
    {
        $this->allowedExtensions = $allowedExtensions;
    }

    /**
     * @return bool
     */
    public function isOnlyImages()
    {
        return $this->onlyImages;
    }

    /**
     * @param bool $onlyImages
     */
    public function setOnlyImages($onlyImages)
    {
        $this->onlyImages = $onlyImages;
    }

    /**
     * @return int
     */
    public function getMaxSize()
    {
        return $this->maxSize;
    }

    /**
     * @param int $maxSize
     */
    public function setMaxSize($maxSize)
    {
        $this->maxSize = $maxSize;
    }

    /**
     * @return int
     */
    public function getConnectionType()
    {
        return $this->connectionType;
    }

    /**
     * @param int $connectionType
     */
    public function setConnectionType($connectionType)
    {
        $this->connectionType = $connectionType;
    }


}