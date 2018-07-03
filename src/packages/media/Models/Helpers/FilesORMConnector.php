<?php
/**
 * Created by PhpStorm.
 * User: artur.kmera
 * Date: 02.07.2018
 * Time: 14:53
 */

namespace Arrow\Media\Models\Helpers;


use Arrow\Common\Models\Helpers\FormHelper;
use Arrow\Media\Models\MediaAPI;
use Arrow\ORM\Persistent\PersistentObject;

class FilesORMConnector
{


    const CONN_SINGLE = 1;
    const CONN_MULTI = 2;

    private static $files;
    private static $inputNamespace;

    /**
     * @param mixed $files
     */
    public static function setInputNamespace($namespace): void
    {
        self::$inputNamespace = $namespace;
    }

    public static function refreshFilesConnection(PersistentObject $object)
    {
        $name = $object instanceof InterfaceIdentifiableClass ? $object->getClassID() : $object::getClass();

        $media = MediaAPI::getMedia($object);

        $result = [];

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

        self::$files[$name][$object->getPKey()] = $result;
    }

    public static function registerField(PersistentObject $object, $fieldName, $connType = self::CONN_MULTI)
    {
        $name = $object instanceof InterfaceIdentifiableClass ? $object->getClassID() : $object::getClass();


        /** @var  PersistentObject $this */


        $object->addVirtualField(
            $fieldName,
            function ($field, $obj) use ($name, $connType) {
                $key = $obj->getPKey();
                if (!isset(self::$files[$name][$key])) {
                    self::refreshFilesConnection($obj);
                }
                if ($connType & self::CONN_MULTI) {
                    if (isset(self::$files[$name][$key][$field])) {
                        return self::$files[$name][$key][$field];
                    } else {
                        return [];
                    }
                } else if ($connType & self::CONN_SINGLE) {
                    if (isset(self::$files[$name][$key][$field])) {
                        return self::$files[$name][$key][$field][0];
                    } else {
                        return null;
                    }
                }

            },
            function ($field, $value, $obj) use ($name, $connType) {
                $key = $obj->getPKey();

                if (!isset(self::$files[$name][$key])) {
                    self::refreshFilesConnection($obj);
                }

                FormHelper::bindFilesToObject(
                    $obj,
                    [$field => $value],
                    FormHelper::getOrganizedFiles(self::$inputNamespace)
                );

            }
        );


    }


}