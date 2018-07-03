<?php

namespace Arrow\Media\Models;


use Arrow\Models\Project;
use Arrow\ORM\ORM_Arrow_Media_Models_Element;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\Persistent\PersistentObject;
use Arrow\RequestContext;
use Arrow\Utils\Models\Helpers\StringHelper;

class Element extends ORM_Arrow_Media_Models_Element
{

    public static $forceNoDeleteSource = false;

    /**
     * @param mixed $folder Mixed
     * @param string $file Path to file
     * @param string $nameSuggestion Name suggestion
     * @param bool $delete Delete orginal
     * @return Element
     */
    public static function createFromFile($folder, $file, $nameSuggestion = "", $delete = false, $realName = false)
    {

        if (!is_object($folder))
            $folder = MediaAPI::getFolderBySystemName($folder);

        $element = new Element(array(
            Element::F_FOLDER_ID => $folder->getPKey()
        ));

        $path = self::appendFile($folder, $file, $nameSuggestion, $delete, $realName);

        $path_parts = pathinfo($path);

        $element[Element::F_CHOSEN] = 0;
        $element[Element::F_PATH] = $path;
        $element[Element::F_NAME] = $path_parts['filename'];
        $element[Element::F_FILE] = $path_parts['basename'];
        $element->save();

        return $element;
    }

    public static function uploadToObject($object, $connName, $uploadName, $uploadPath, $folder = false)
    {

        if (!$folder) {
            $folder = MediaAPI::getFolderBySystemName(str_replace("\\", "_", get_class($object)));
            $folder = $folder->getPKey();
        }

        $element = new Element(array(self::F_FOLDER_ID => $folder), array(
            "connection" => array(
                ElementConnection::F_MODEL => get_class($object),
                "key" => $object->getPKey(),
                ElementConnection::F_NAME => $connName,
                "direct" => 1,
            ),
            "upload_name" => $uploadName,
            "upload_path" => $uploadPath));

        $element->save();

        return $element;
    }

    private static function appendFile($folder, $file, $nameSuggestion = "", $delete = false, $realName = false)
    {
        if (!is_object($folder))
            $folder = MediaAPI::getSystemFolder();

        $folderPath =/* MediaAPI::getBasePath().*/
            $folder[Folder::F_PATH];


        $path_parts = pathinfo($realName ? $realName : $file);


        if ($nameSuggestion)
            $name = StringHelper::toValidFilesystemName($nameSuggestion, false);
        else
            $name = str_replace("." . $path_parts['extension'], "", $path_parts['basename']);


        if (!file_exists(MediaAPI::getBasePath() . $folderPath)) {
            //exit(MediaAPI::getBasePath().$folderPath);
            mkdir(MediaAPI::getBasePath() . $folderPath, 0777, true);
        }


        $nameWithExtension = StringHelper::toValidFilesystemName($name);
        $path = MediaAPI::getBasePath() . "./" .$folderPath . "/" . $nameWithExtension;

        $i = 1;
        while (file_exists(MediaAPI::getBasePath() . $path)) {
            $nameWithExtension = StringHelper::toValidFilesystemName($path_parts['filename'] . $i . "." . $path_parts['extension']);
            $path = $folderPath . "/" . $nameWithExtension;
            $i++;
        }


        if (!@copy($file, MediaAPI::getBasePath() . $path) && !file_exists(MediaAPI::getBasePath() . $path)) {
            throw new \Arrow\Exception([
                "msg" => "File copy error",
                "source" => MediaAPI::getBasePath() . $path,
                "file" => $file,
                "file_exists" => is_readable($file),
                "path" => $path,

            ]);
        }


        if ($delete && !self::$forceNoDeleteSource)
            unlink($file);

        return $path;
    }

    public function beforeObjectCreate(PersistentObject $object)
    {
        $rq = RequestContext::getDefault();

        //@todo sprawdzic czemu isset
        if (isset($_FILES[$rq["name"]]["name"]) && self::isImageFile($_FILES[$rq["name"]]["name"]) && $rq["imgMinSize"]) {
            $size = explode(",", $rq["imgMinSize"]);
            $imSize = getimagesize($_FILES[$rq["name"]]["tmp_name"]);

            if ($imSize[0] < $size[0] || $imSize[1] < $size[1])
                exit("Minimalne wymiary dla tego zdjęcia to {$size[0]}x{$size[1]}px \nTo zdjęcie posiada wymiary {$imSize[0]}x{$imSize[1]}px");
        }
    }


    public function afterObjectCreate(PersistentObject $object)
    {

        $db = Project::getInstance()->getDB();
        $this[Element::F_SORT] = $db->query("select max(sort) from " . self::getTable())->fetchColumn() + 1;
        $this->save();

        if (isset($this->parameters["upload_name"]) || isset($_FILES["file"]["name"])) {


            $folder = Criteria::query(Folder::getClass())->findByKey($this[self::F_FOLDER_ID]);


            $name = isset($this->parameters["upload_name"]) ? $this->parameters["upload_name"] : $_FILES["file"]["name"];
            $name = isset($this->parameters["name_suggestion"]) ? $this->parameters["name_suggestion"] : $name;
            $path = isset($this->parameters["upload_path"]) ? $this->parameters["upload_path"] : $_FILES["file"]["tmp_name"];
            $path = self::appendFile($folder, $path, $name, true, isset($this->parameters["upload_name"]) ? $this->parameters["upload_name"] : $_FILES["file"]["name"]);

            $path_parts = pathinfo($path);
            $this[Element::F_PATH] = $path;
            $this[Element::F_NAME] = $path_parts['filename'];
            $this[Element::F_FILE] = $path_parts['basename'];


            $this->save();
        }

        if (isset($this->parameters["connection"])) {
            $con = $this->parameters["connection"];
            if (isset($con["delete_old"]) && $con["delete_old"]) {
                $criteria = new Criteria();
                $criteria->addCondition(ElementConnection::F_MODEL, $con["model"]);
                $criteria->addCondition(ElementConnection::F_OBJECT_ID, $con["key"]);
                $criteria->addCondition(ElementConnection::F_NAME, $con["name"]);
                $conn = ElementConnection::getByCriteria($criteria, ElementConnection::TCLASS);
                foreach ($conn as $c) $c->delete();

            }
            $ElementConnection = new ElementConnection(array(
                ElementConnection::F_ELEMENT_ID => $this->getPKey(),
                ElementConnection::F_MODEL => $con["model"],
                ElementConnection::F_OBJECT_ID => $con["key"],
                ElementConnection::F_NAME => $con["name"],
                ElementConnection::F_DIRECT => $con["direct"]

            ));
            $ElementConnection->save();
        }

        $rq = RequestContext::getDefault();
        if ($rq["maxSize"]) {
            $size = explode(",", $rq["maxSize"]);

            $imTransform = new \ImageTransform();
            $imTransform->load($this[Element::F_PATH]);
            $imTransform->setTargetFile($this[Element::F_PATH]);
            $imTransform->resizeToWidth = $size[0];
            $imTransform->resizeToHeight = $size[1];
            $imTransform->resize();


        }


        parent::afterObjectCreate($object);
    }

    public static function isImageFile($file)
    {
        $images = array("jpg", "jpeg", "gif", "png", "bmp");
        $tmp = explode(".", $file);
        $ex = array_pop($tmp);
        $ex = strtolower($ex);
        return in_array($ex, $images);
    }

    public function isImage()
    {
        return self::isImageFile($this[self::F_FILE]);
    }

    public function refreshImageCache()
    {
        MediaAPI::refreshImageCache($this);
    }


    public function setValue($field, $val, $tmp = false)
    {
        if (false && $field == self::F_FOLDER_ID && $val != $this[self::F_FOLDER_ID]) {
            parent::setValue($field, $val, $tmp);
            return MediaApi::moveElement($this, $val, true);
        }

        if ($field == 'media')
            $tmp = true;

        return parent::setValue($field, $val, $tmp);
    }

    public function save($forceInsert = false)
    {
        /*if(isset($_FILES["file"])){
              $tmp = explode( ".", $_FILES["file"]["name"]);
              //$filename = md5($_FILES["file"]["name"]).".".array_pop($tmp);
              $filename = $_FILES["file"]["name"];
              $this[self::F_SIZE] = $_FILES["file"]["size"];
              $path = $this->getFilePath(true);
              unlink($path);
              if(!copy($_FILES['file']['tmp_name'], $path))
                  throw new \Arrow\Exception(array("msg" => "File upload error", "file" => $_FILES["file"]["name"]));

              if($this[self::F_TRANSFORMATION_ID]){
                  $profile = MediaTransformationProfile::getByKey($this[self::F_TRANSFORMATION_ID], MediaTransformationProfile::TCLASS);
                  $profile->process( $this );
              }
          }*/
        if (file_exists($this[self::F_PATH]))
            $this[self::F_SIZE] = filesize($this[self::F_PATH]);


        $this[Element::F_CHOSEN] = 0;

        $ret = parent::save();

        return $ret;
    }

    public function getValue($field)
    {
        if ($field == self::F_SIZE)
            return $this->getFileSize(parent::getValue($field));
        return parent::getValue($field);
    }

    public function delete()
    {

        $file = MediaAPI::getBasePath() . $this[self::F_PATH];
        if (file_exists($file) && is_file($file))
            unlink($file);


        $el = ElementConnection::get()
            ->c("element_id", $this->getPKey())
            ->find();

        foreach ($el as $e)
            $e->delete();

        parent::delete();
    }

    function getFileSize($sizeInBytes, $sizeIn = 'MB')
    {
        return $sizeInBytes;
        if ($sizeIn == 'B') {
            $size = $sizeInBytes;
            $precision = 0;
        } elseif ($sizeIn == 'KB' || $sizeInBytes / 1024 < 200) {
            $size = (($sizeInBytes / 1024));
            $precision = 1;
            $sizeIn = 'KB';
        } elseif ($sizeIn == 'MB') {
            $size = (($sizeInBytes / 1024) / 1024);
            $precision = 1;
        } elseif ($sizeIn == 'GB') {
            $size = (($sizeInBytes / 1024) / 1024) / 1024;
            $precision = 1;
        }

        $size = round($size, $precision);

        return $size . ' ' . $sizeIn;
    }


    //*END OF USER AREA*//
}

?>