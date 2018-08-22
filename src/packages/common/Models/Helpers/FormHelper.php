<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 27.08.2017
 * Time: 11:06
 */


namespace Arrow\Common\Models\Helpers;

use function array_keys;
use Arrow\Media\Models\Element;
use Arrow\Media\Models\ElementConnection;
use Arrow\Media\Models\MediaAPI;
use Arrow\Models\ExceptionHandler;
use Arrow\ORM\Persistent\PersistentObject;
use function basename;
use const PHP_EOL;
use function strpos;
use Symfony\Component\HttpFoundation\File\File;
use function var_dump;

class FormHelper
{

    public static function replaceObjectFiles(PersistentObject $object, $namespace = "data")
    {
        $files = self::getOrganizedFiles(false, $namespace);
        foreach ($files as $name => $elements) {
            MediaAPI::removeFilesFromObject($object, $name);
            foreach ($elements as $element) {
                MediaAPI::addFileToObject($object, $name, $element["name"], $element["tmp_name"]);
            }
        }
    }

    public static function bindFilesToForm($object)
    {
        $media = MediaAPI::getMedia($object);

        $result = [];
        if (count($media) <= 0) {
            $result = [
                "images" => []
            ];
        }
        foreach ($media as $connName => $files) {
            $result[$connName] = [];
            foreach ($files as &$file) {
                $isImage = false;
                $result[$connName][] = [
                    "key" => $file["id"],
                    "name" => $file["name"],
                    "size" => @filesize($file["path"]),
                    "description" => "",
                    "title" => "",
                    "type" => $isImage ? "image" : "document",
                    "uploaded" => true,
                    "path" => $file["path"]
                ];
            }
        }

        return $result;

    }

    public static function bindFilesToObject(PersistentObject $object, $filesData, $upload)
    {


        foreach ($upload as $connName => $files) {
            foreach ($files as $file) {
                MediaAPI::addFileToObject($object, $connName, $file["name"], $file["tmp_name"]);
            }
        }


        $media = MediaAPI::getMedia($object);

        // lookup to delete
        foreach ($media as $connName => $files) {

            foreach ($files as $file) {
                $exists = false;
                if (isset($filesData[$connName]) && is_array($filesData[$connName])) {
                    foreach ($filesData[$connName] as &$inFile) {
                        //checking that file which exist in server exist in incoming file list
                        if (
                            $file["id"] == $inFile["key"] ||
                            !$inFile["key"] && $inFile["size"] == filesize($file["path"])
                        ) {
                            $exists = true;
                            $inFile["key"] = $file["id"];
                            break;
                        }
                    }
                }
                if (!$exists) {
                    Element::get()->findByKey($file["id"])->delete();
                }
            }
        }

        //sorting
        foreach ($filesData as $connName => $elements) {
            if ($elements) {
                foreach ($elements as $index => $element) {
                    //just uploaded dont have keys
                    if ($element["key"]) {
                        //$sort[$element["key"]] = $index;
                        if ($object->getPKey()) {
                            $el = ElementConnection::get()
                                ->_objectId($object->getPKey())
                                ->_model($object->getClass())
                                ->_elementId($element["key"])
                                ->findFirst();
                        } else {
                            $el = ElementConnection::get()
                                ->_elementId($element["key"])
                                ->findFirst();
                        }
                        $el["sort"] = $index;
                        if (gettype($el) == "object") {
                            $el->save();
                        }
                    }
                }
            }
        }

        foreach ($media as $connName => $files) {
            foreach ($files as $file) {
                if (Element::get()->findByKey($file["id"])) {
                    $el = Element::get()->findByKey($file["id"])->_file();
                    $prepareObjectName = str_replace("\\", "_", $object->getClass());
                    $fileDir = "data/uploads/System/" . $prepareObjectName . "/";

                    $fileTarget = $fileDir . basename($el);

                    if(strpos($_SERVER["HTTP_HOST"], "esotiq") !== false) {  
                        sleep(0.5);
                        if (file_exists($fileTarget)) {
                            $fileS = new File($fileTarget);
                            $fileS->move("/srv/http/3code/static.esotiq.com/data/uploads/System/" . $prepareObjectName, $el);
                        }
                    }

                }
            }
        }


    }

    public static function getFixedFilesArray()
    {
        $walker = function ($arr, $fileInfokey, callable $walker) {
            //print_r( $arr) . " -- <br />";
            $ret = array();
            foreach ($arr as $k => $v) {
                if (is_array($v)) {
                    $key = array_keys($v)[0];
                    if ($key === "nativeObj") {
                        $ret[$k][$fileInfokey] = $v[$key];
                    } else {
                        $ret[$k] = $walker($v, $fileInfokey, $walker);
                    }
                } else {
                    $ret[$k][$fileInfokey] = $v;


                }
            }
            return $ret;
        };

        $files = array();
        foreach ($_FILES as $name => $values) {

            // init for array_merge
            if (!isset($files[$name])) {
                $files[$name] = array();
            }
            if (!is_array($values['error'])) {
                // normal syntax
                $files[$name] = $values;
            } else {
                // html array feature
                foreach ($values as $fileInfoKey => $subArray) {
                    $files[$name] = array_replace_recursive($files[$name], $walker($subArray, $fileInfoKey, $walker));
                }
            }
        }

        return $files;
    }

    public static function getOrganizedFiles($namespace = false)
    {

        if (!isset($_FILES)) {
            return [];
        }

        $ret = self::getFixedFilesArray();
        if ($namespace) {
            $tmp = explode(".", $namespace);
            foreach ($tmp as $el) {
                if (isset($ret[$el])) {
                    $ret = $ret[$el];
                } else {
                    return [];
                }
            }
        }


        return $ret;
    }

    public static function assocToOptions($in)
    {
        $tmp = [];
        foreach ($in as $key => $value) {
            $tmp[] = ["value" => $key, "label" => $value];
        }
        return $tmp;
    }


}
