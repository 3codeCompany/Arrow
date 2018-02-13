<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 13.02.2018
 * Time: 15:34
 */

namespace Arrow\Media\Models;


trait FileAwareObject
{

    private $files = null;
    private $filesLoaded = false;

    private function loadFiles()
    {
        $this->files = MediaAPI::getMedia($this);
        $this->filesLoaded = true;
    }

    public function getFiles($key = null)
    {
        if (!$this->filesLoaded) {
            $this->loadFiles();
        }

    }

    public function removeAllFiles()
    {
        if (!$this->filesLoaded) {
            $this->loadFiles();
        }

    }

}
