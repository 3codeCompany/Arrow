<?php
/**
 * Created by PhpStorm.
 * User: artur.kmera
 * Date: 02.07.2018
 * Time: 14:53
 */

namespace Arrow\Media\Models\Helpers;


use Arrow\Access\Models\Auth;
use Arrow\Common\Models\Exceptions\NotImplementedException;
use Arrow\Common\Models\Helpers\FormHelper;
use Arrow\Common\Models\Interfaces\InterfaceIdentifiableClass;
use Arrow\Kernel;
use Arrow\Media\Models\Element;
use Arrow\Media\Models\ElementConnection;
use Arrow\Media\Models\MediaAPI;
use Arrow\Models\DB;
use Arrow\ORM\Persistent\DataSet;
use Arrow\ORM\Persistent\PersistentObject;
use Arrow\Utils\Models\Helpers\StringHelper;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Validation;


class FilesORMConnector
{


    const CONN_SINGLE = 1;
    const CONN_MULTI = 2;

    private static $files;
    private $inputNamespace;

    private $encodeFileName = true;
    private $targetFolder = ARROW_DATA_PATH . "/uploads/storage";
    private $useRelativePath = true;
    private $downloadPathGenerator;
    private $registredNames = [];

    /**
     * @var Validation
     */
    private  $validatorData;


    /** @var DB $db */
    private $db;

    public function __construct()
    {
        $this->db = Kernel::$project->getContainer()->get(DB::class);

        $this->downloadPathGenerator = function (string $path, array $data, PersistentObject $object) {
            return "/common/download/" . $data["elementId"];
        };

    }

    function useRelativePaths($flag)
    {

        $this->useRelativePath = $flag;

    }

    function setTargetFolder(string $targetFolder)
    {
        $this->targetFolder = $targetFolder;
    }

    function setEncodeFilename($flag)
    {

        $this->encodeFileName = $flag;

    }

    function getRelativePath($base, $path)
    {
        // Detect directory separator
        $separator = substr($base, 0, 1);
        $base = array_slice(explode($separator, rtrim($base, $separator)), 1);
        $path = array_slice(explode($separator, rtrim($path, $separator)), 1);

        return $separator . implode($separator, array_slice($path, count($base)));
    }

    /**
     * @param mixed $files
     * @return FilesORMConnector
     */
    public function setInputNamespace($namespace)
    {
        $this->inputNamespace = $namespace;
        return $this;
    }

    public function refreshFilesConnection(PersistentObject $object)
    {
        $name = $object instanceof InterfaceIdentifiableClass ? $object->getClassID() : $object::getClass();
        $key = $object->getPKey();

        $prepared = self::getMedia([$object]);
        $media = isset($prepared[$key]) ? $prepared[$key] : [];

        $result = [];

        foreach ($media as $connName => $files) {
            $result[$connName] = [];
            /** @var ConnectedFileInfo $file */
            foreach ($files as $file) {
                $isImage = false;
                $result[$connName][] = [
                    "key" => $file->elementId,
                    "name" => $file->name,
                    "size" => $file->size,
                    "description" => "",
                    "title" => "",
                    "type" => $isImage ? "image" : "document",
                    "uploaded" => true,
                    "path" => $file->path
                ];
            }
        }


        self::$files[$name][$key] = $result;

        foreach ($this->registredNames as $name) {
            if (!isset(self::$files[$name][$key])) {
                self::$files[$name][$key] = [];
            }
        }
    }

    public function registerField(PersistentObject $object, $fieldName, FilesORMConnectorConfig $config = null)
    {
        if($config == null){
            $config = new FilesORMConnectorConfig();
        }

        $connType = $config->getConnectionType();

        $name = $object instanceof InterfaceIdentifiableClass ? $object->getClassID() : $object::getClass();

        $this->registredNames[] = $fieldName;

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
                if ($value == "") {
                    $value = [];
                }

                $key = $obj->getPKey();

                if (!isset(self::$files[$name][$key])) {
                    self::refreshFilesConnection($obj);
                }


                $preservedKeys = array_map(function ($el) {
                    return $el["key"];
                }, $value);

                if (isset(self::$files[$name][$key][$field])) {

                    $currentKeys = array_map(function ($el) {
                        return $el["key"];
                    }, self::$files[$name][$key][$field]);

                    $diference = array_diff($currentKeys, $preservedKeys);

                    foreach ($diference as $key) {
                        //uploaded files keys are empty
                        if ($key) {
                            $this->deleteElementConnection($key);
                        }
                    }
                }


                /** @var Request $request */
                $request = Kernel::$project->getContainer()->get(Request::class);

                $uploadedList = $request->files->get($this->inputNamespace);

                if ($uploadedList !== null && isset($uploadedList[$field])) {
                    foreach ($uploadedList[$field] as $element) {
                        /** @var UploadedFile $uploaded */
                        if (isset($element["nativeObj"])) {
                            $uploaded = $element["nativeObj"];
                        } else {
                            $uploaded = $element;
                        }
                        $this->bindUploadedFileToObject($obj, $uploaded, $field);
                    }
                }

                self::refreshFilesConnection($obj);

            }
        );

    }

    public function getObjectFiles(PersistentObject $object)
    {
        $name = $object instanceof InterfaceIdentifiableClass ? $object->getClassID() : $object::getClass();
        $key = $object->getPKey();

        if(!isset(self::$files[$name][$key])) {
            self::refreshFilesConnection($object);
        }

        return self::$files[$name][$key];
    }

    public function bindUploadedFileToObject(PersistentObject $object, UploadedFile $file, string $connectionName)
    {

        $classId = $object instanceof InterfaceIdentifiableClass ? $object->getClassID() : $object::getClass();

        if ($this->encodeFileName) {
            $name = bin2hex(random_bytes(20));
        } else {
            $name = StringHelper::toValidFilesystemName($file->getClientOriginalName());
        }

        $iterator = 1;
        while (file_exists($this->targetFolder . DIRECTORY_SEPARATOR . $name)) {
            if ($this->encodeFileName) {
                $name = bin2hex(random_bytes(20));
            } else {
                $extension = $file->getClientOriginalExtension();
                $basename = substr($file->getClientOriginalName(), 0, -(strlen($extension) + 1));
                $name = $basename . "_" . $iterator . "." . $extension;
                $iterator++;
            }
        }


        /** @var Auth $auth */
        $auth = Kernel::$project->getContainer()->get(Auth::class);


        $userId = $auth->getUser() ? $auth->getUser()->getPKey() : -1;

        $moved = null;
        try {
            $this->db->beginTransaction();

            if ($this->useRelativePath && substr($this->targetFolder, 0, 1) !== ".") {
                $folderToSave = "." . $this->getRelativePath(ARROW_DOCUMENTS_ROOT, $this->targetFolder);
            } else {
                $folderToSave = $this->targetFolder;
            }

            $element = Element::create([
                Element::F_CREATED => date("Y-m-d H:i:s"),
                Element::F_CREATED_BY => $userId,
                Element::F_NAME => $file->getClientOriginalName(),
                Element::F_FILE => $name,
                Element::F_PATH => $folderToSave . DIRECTORY_SEPARATOR . $name,
                Element::F_SIZE => $file->getSize(),

            ]);

            $conn = ElementConnection::create([
                ElementConnection::F_NAME => $connectionName,
                ElementConnection::F_MODEL => $classId,
                ElementConnection::F_OBJECT_ID => $object->getPKey(),
                ElementConnection::F_ELEMENT_ID => $element->_id(),
            ]);

            $moved = $file->move($this->targetFolder, $name);


            $this->db->commit();
        } catch (\Exception $ex) {

            $this->db->rollBack();
            if (file_exists($file->getPathname())) {
                unlink($file->getPathname());
            }
            if ($moved !== null) {
                if (file_exists($moved->getPathname())) {
                    unlink($moved->getPathname());
                }
            }

            throw $ex;

        }

    }

    public function deleteElementConnection($elementId)
    {
        $element = Element::get()->findByKey($elementId);
        if (file_exists($element["path"])) {
            unlink($element["path"]);
        }
        $element->delete();

        $conn = ElementConnection::get()
            ->_elementId($elementId)
            ->findFirst();

        if ($conn) {
            $conn->delete();
        }
    }

    private static function getMedia($objectList, array $fieldsToGet = null)
    {

        if (empty($objectList)) {
            return false;
        }

        if (is_array($objectList)) {
            $testElement = reset($objectList);
        } elseif ($objectList instanceof DataSet) {
            $objectList = $objectList->toArray();
            $testElement = reset($objectList);
        } else {
            throw new \Exception("input elements have to be PersistentObject[] or DataSet<PersistentObject>");
        }

        $objectsKeys = array_map(function ($el) {
            return $el->getPKey();
        }, $objectList);

        $classId = $testElement instanceof InterfaceIdentifiableClass ? $testElement->getClassID() : $testElement::getClass();

        /** @var DB $db */
        $db = Kernel::$project->getContainer()->get(DB::class);

        $connName = "";
        if ($fieldsToGet !== null && is_array($fieldsToGet)) {
            $connName = "AND media_element_connections.`name` IN ('" . implode("','", $fieldsToGet) . "')";
        }

        $q = "
		SELECT 
		media_element_connections.`id` as 'Conn:id', 
		media_element_connections.`name` AS `Conn:name`, 
		media_element_connections.`object_id` as `Conn:object_id`,  
		media_element_connections.`data` as `Conn:data`,

		media_elements.`id` as `Element:id`,
		media_elements.`name` as `Element:name`,
		media_elements.`path` as `Element:path`,
		media_elements.`size` as `Element:size`

		FROM media_elements
		left JOIN media_element_connections ON (media_elements.id=media_element_connections.element_id ) 
		WHERE
		media_element_connections.`model` = '" . addslashes($classId) . "'
		AND media_element_connections.`object_id` IN ('" . implode("','", $objectsKeys) . "')
		{$connName} 
		ORDER BY  media_element_connections.`sort` ASC,media_elements.sort ASC";


        $result = $db->query($q)->fetchAll(\PDO::FETCH_ASSOC);

        $return = array();
        foreach ($result as $row) {
            if (!isset($return[$row["Conn:object_id"]])) {
                $return[$row["Conn:object_id"]][$row["Conn:name"]] = [];
            }

            $fInfo = new ConnectedFileInfo();
            $fInfo->elementId = $row["Element:id"];
            $fInfo->connectionId = $row["Conn:id"];
            $fInfo->connectionData = $row["Conn:data"];
            $fInfo->name = $row["Element:name"];
            $fInfo->size = $row["Element:size"];
            $fInfo->path = $row["Element:path"];

            $return[$row["Conn:object_id"]][$row["Conn:name"]][] = $fInfo;

        }

        return $return;

    }

    /**
     * @return Validation
     */
    public function getValidatorData()
    {
        return $this->validatorData;
    }

    /**
     * @param Validation $validatorData
     */
    public function setValidatorData(Collection $validatorData)
    {
        $this->validatorData = $validatorData;
        return $this;
    }




}