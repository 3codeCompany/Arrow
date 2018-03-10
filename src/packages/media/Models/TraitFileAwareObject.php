<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 13.02.2018
 * Time: 15:34
 */

namespace Arrow\Media\Models;


use Arrow\Common\Models\Helpers\FormHelper;
use Arrow\ORM\Persistent\PersistentObject;

trait TraitFileAwareObject
{

    private $filesNamespace = "files";
    private $files = null;


    public function __filesInitConnection()
    {
        /** @var  PersistentObject $this */
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
    }


}
