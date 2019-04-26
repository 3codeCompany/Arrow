<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 13.02.2018
 * Time: 15:34
 */

namespace Arrow\Media\Models\Helpers;


use Arrow\Common\Models\Helpers\FormHelper;
use Arrow\Media\Models\Helpers\FilesORMConnector;
use Arrow\ORM\Persistent\PersistentObject;

trait TraitFileAwareObject
{

    /**
     * @var FilesORMConnector
     */
    protected $filesConnector = null;

    /**
     * @return FilesORMConnector
     */
    public function getFilesConnector(): FilesORMConnector
    {

        if ($this->filesConnector === null) {
            $this->filesConnector = new FilesORMConnector();
            $this->filesConnector->useRelativePaths(false);
        }
        return $this->filesConnector;
    }

    public function getFileByKey($key)
    {
        $files = $this->getFilesConnector()->getObjectFiles($this);

        foreach ($files as $typeName => $files) {
            foreach ($files as $file) {
                if ($file["key"] == $key) {
                    return $file;
                }
            }
        }

    }


    /*

        private $filesNamespace = "files";
    private $files = null;

      public function __filesInitConnection()
    {

        $this->files = MediaAPI::getMedia($this);

        $this->addVirtualField(
            $this->filesNamespace,
            function () {
                return $this->files;
            },
            function ($value) {
                $this->files = $value;
            }
        );


        return $this;
    }

    public function __filesAttachUploaded($namespace)
    {
        if ($this->files === null) {
            throw new \RuntimeException("Init before attach uploaded");
        }
        FormHelper::bindFilesToObject(
            $this,
            $this->files,
            FormHelper::getOrganizedFiles($namespace)
        );
        return $this;
    }*/


}
