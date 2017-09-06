<?php

namespace Arrow\Media\Models;

use function array_keys;
use Arrow\Models\Project;
use Arrow\ORM\Persistent\Criteria, Arrow\ORM\SqlRouter;
use Arrow\ORM\JoinCriteria;
use Arrow\ORM\Persistent\PersistentObject;
use Arrow\Package\Application\Product;


class MediaAPI
{

    const VERSIONS_VAR = "ver";
    const MEDIA_VAR = "media";

    const SYSTEM_FOLDER_NAME = "system.container";

    const ELEMENTS = 1;
    const ELEMENTS_PATH = 2;
    const VERSIONS = 4;
    const VERSIONS_PATH = 8;


    const NONE = 0;
    const ALL = 1;

    private static $basePath;
    private static $baseURL;

    /**
     * @return mixed
     */
    public static function getBaseURL()
    {
        return self::$baseURL;
    }

    /**
     * @param mixed $baseURL
     */
    public static function setBaseURL($baseURL)
    {
        self::$baseURL = $baseURL;
    }

    /**
     * @return mixed
     */
    public static function getBasePath()
    {
        return self::$basePath;
    }

    /**
     * @param mixed $basePath
     */
    public static function setBasePath($basePath)
    {
        self::$basePath = $basePath;
    }


    /**
     * Returns root folder
     *
     * @return Folder
     */
    public static function getRootFolder()
    {
        return Folder::getRoot();
    }

    /**
     * Returns system folder
     *
     * @return Folder
     */
    public static function getSystemFolder()
    {
        $result = self::getFolderBySystemName(self::SYSTEM_FOLDER_NAME, false);
        if ($result == false) {
            $result = self::createFolder(self::getRootFolder(), "System", self::SYSTEM_FOLDER_NAME);
        }
        return $result;
    }

    /**
     * Creates folder
     * @param mixed $parent folder parent
     * @param string $name folder name
     * @param string $systemName system name
     * @return Folder
     */
    public static function createFolder($parent, $name, $systemName = "")
    {
        if (!($parent instanceof Folder)) {
            $parent = Criteria::query(Folder::getClass())->findByKey($parent);
        }
        $folder = new Folder(array(
            Folder::F_SYSTEM_NAME => $systemName,
            Element::F_NAME => $name,
            Folder::F_PARENT_ID => $parent->getPKey(),
        ));
        $folder->save();
        return $folder;
    }


    public static function setObjectFile($object, $connName, $uploadName, $uploadPath)
    {
        $media = MediaAPI::getMedia($object);
        if (isset($media[$connName])) {
            foreach ($media[$connName] as $el) {
                Element::get()->findByKey($el["id"])->delete();
            }
        }

        return Element::uploadToObject($object, $connName, $uploadName, $uploadPath);
    }


    public static function removeFilesFromObject($object, $connName = false)
    {

        $tmp = explode('\\', $object::getclass());
        $class = end($tmp);

        $criteria = ElementConnection::get()
            ->_objectId($object->_id())
            ->_model("%" . $class, Criteria::C_LIKE);
        if ($connName) {
            $criteria->_name($connName);
        }

        $conn = $criteria
            ->findAsFieldArray(ElementConnection::F_ELEMENT_ID, true);


        $connId = array_keys($conn);


        $ele = Element::get()
            ->_id($conn, Criteria::C_IN)
            ->find();


        foreach ($ele as $e) {
            $e->delete();
        }

        $elements = ElementConnection::get()
            ->_id($connId, Criteria::C_IN)
            ->find();

        foreach ($elements as $e) {
            $e->delete();
        }


    }

    public static function addFileToObject($object, $connName, $uploadName, $uploadPath)
    {
        return Element::uploadToObject($object, $connName, $uploadName, $uploadPath);
    }

    public static function createElement($el, $parent, $filePath, $newFileName = "", $name = false)
    {
        if (!($parent instanceof Folder)) {
            $parent = Criteria::query(Folder::getClass())->findByKey($parent);
        }


        $fileSysName = basename($filePath);

        $tmp = explode(".", $newFileName);
        $extension = end($tmp);

        if ($newFileName == "") {
            $filename = str_replace(array("%"), array("_"), $name ? $name . "." . $extension : $fileSysName);
        } else {
            $filename = $newFileName;
        }
        $explodedName = explode(".", $filename);

        $filename = \Arrow\Utils\StringHelper::toValidFilesystemName($filename);


        //$el = Element::create(array(
        /*$el = \Arrow\Package\Database\ProjectPersistent::create(array(
             Element::F_FOLDER_ID => $parent->getPKey(),
             Element::F_NAME => $explodedName[0],
             Element::F_FILE => $filename
        ), Element::TCLASS);*/


        $el->setValues(array(
            Element::F_FOLDER_ID => $parent->getPKey(),
            Element::F_NAME => $explodedName[0],
            Element::F_FILE => $filename
        ));

        //$el->save();

        $i = 1;
        $criteria = Criteria::query(Element::getClass());
        $criteria->addCondition(Element::F_FILE, $filename);
        $criteria->addCondition(Element::F_FOLDER_ID, $el[Element::F_FOLDER_ID]);
        $test = $criteria->find();


        $path = $parent["path"] . "/" . $el[Element::F_FILE];
        if ($path != $filePath) { /*for import*/
            while (file_exists($path) || !empty($test)) {
                if (isset($explodedName[1])) {
                    $el[Element::F_FILE] = \Arrow\Utils\StringHelper::toValidFilesystemName($explodedName[0] . "($i)." . $explodedName[1]);
                } else {
                    $el[Element::F_FILE] = \Arrow\Utils\StringHelper::toValidFilesystemName($explodedName[0] . "($i)");
                }
                $el[Element::F_NAME] = $explodedName[0] . "($i)";
                $i++;

                $criteria = Criteria::query(Element::getClass());
                $criteria->addCondition(Element::F_FILE, $el[Element::F_FILE]);
                $criteria->addCondition(Element::F_FOLDER_ID, $el[Element::F_FOLDER_ID]);
                $test = $criteria->find();

                $path = $parent["path"] . "/" . $el[Element::F_FILE];

                if ($i > 200) {
                    throw new \Arrow\Exception("[MediaApi] Istenieje 200 wersji uploodowanego pliku :" . $path);
                }

            }
        }
        if (!copy($filePath, $path) && !file_exists($path) /*import*/) {
            throw new \Arrow\Exception(array("msg" => "File copy error", "file" => $filePath));
        }

        chmod($path, 0777);
        $el[Element::F_PATH] = $path;
        $el->save();


        //if($el->isImage())
        //	$el->createSystemMiniature();

        $el[Element::F_SORT] = $el->getPKey();
        $el->save();


        return $el;
    }

    public static function moveElement($el, $newParent, $parentChanged = false)
    {
        if (!($newParent instanceof Folder)) {
            $newParent = Criteria::query(Folder::getClass())->findByKey($newParent);
        }

        $i = 0;

        $test = Criteria::query(Element::getClass())
            ->c(Element::F_FILE, $el[Element::F_FILE])
            ->c(Element::F_FOLDER_ID, $newParent["id"])
            ->find();

        $path = $newParent[Folder::F_PATH] . "/" . $el[Element::F_FILE];

        $explodedName = explode(".", $el[Element::F_FILE]);

        while (file_exists($path) || !empty($test)) {
            $i++;
            $el[Element::F_FILE] = $explodedName[0] . "($i)." . $explodedName[1];
            $el[Element::F_NAME] = $explodedName[0] . "($i)";


            $criteria = new Criteria();
            $criteria->addCondition(Element::F_FILE, $el[Element::F_FILE]);
            $criteria->addCondition(Element::F_FOLDER_ID, $newParent["id"]);
            $test = Element::getByCriteria($criteria, Element::TCLASS);

            $path = $newParent->getFilesystemPath() . "/" . $el[Element::F_FILE];

            if ($i > 100) {
                return;
            }

        }
        if (file_exists($el[Element::F_PATH])) {
            if (!rename($el[Element::F_PATH], $path)) {
                throw new \Arrow\Exception(array("msg" => "File copy error", "file" => $el[Element::F_PATH]));
            }
        }


        if (file_exists($el->getSystemMiniature())) {
            if (!rename($el->getSystemMiniature(), dirname($path) . "/mf_mini" . $el["id"] . ".jpg")) {
                throw new \Arrow\Exception(array("msg" => "File copy error", "file" => $el[Element::F_PATH]));
            }
        }


        $old = mysql_escape_string(dirname($el[Element::F_PATH]));
        $new = mysql_escape_string(dirname($path));

        foreach ($el->getVersions() as $ver) {
            //$oldTmp = $old."/".$ver[MediaVersionResult::F_FILE];
            $verName = $ver[MediaVersionResult::F_FILE];
            if ($i > 0) {
                $tmp = explode(".", $verName);
                $tmp[count($tmp) - 2] .= "($i)";
                $verName = implode(".", $tmp);
                $ver[MediaVersionResult::F_FILE] = $verName;
            }

            $newPath = $new . "/" . $verName;
            rename($ver[MediaVersionResult::F_PATH], $newPath);
            $ver[MediaVersionResult::F_PATH] = $newPath;
            $ver->save();
        }

        if (!$parentChanged) {
            $el[Element::F_FOLDER_ID] = $newParent["id"];
        }
        $el[Element::F_PATH] = $path;
        $el->save();
    }


    /**
     * Returns folder with given system name
     *
     * @param string $systemName
     * @param boolean $createIfNotExists
     */
    public static function getFolderBySystemName($systemName, $createIfNotExists = true)
    {
        $folder = Criteria::query(Folder::getClass())
            ->c(Folder::F_SYSTEM_NAME, $systemName)
            ->findFirst();

        if (empty($folder)) {
            if (!$createIfNotExists) {
                return false;
            }
            return self::createFolder(self::getSystemFolder(), $systemName, $systemName);
        }
        return $folder;
    }

    /**
     * Returns media elements connected with object
     * @param Model $obj model object
     * @param string $name elements name
     * @param mixed $arrVersions MediaApi::NONE, MediaApi::All or array witch versions names
     * @param mixed $limit false or int
     * @return Element[]
     */
    public static function getObjElements($obj, $name = false, $limit = false)
    {
        $conC = ElementConnection::getClass();
        $conCC = ElementConnection::getClass() . ":";
        $crit = Element::get()
            ->_join(ElementConnection::getClass(), ["id" => "element_id"], "Element", ["id"], JoinCriteria::J_OUTER)
            ->c("Element:" . ElementConnection::F_MODEL, get_class($obj))
            ->c("Element:" . ElementConnection::F_OBJECT_ID, $obj->getPKey())
            ->order("Element:" . ElementConnection::F_SORT, Criteria::O_ASC);

        if ($name) {
            $crit->c("Element:" . ElementConnection::F_NAME, $name);
        }

        return $crit->find();
    }

    /**
     * Returns arrays media elements connected with objects
     * @param $obj model array("model" => string, "ids" => array of id elements
     * @param string $name elements name
     * @param mixed $arrVersions MediaApi::NONE, MediaApi::All or array witch versions names
     * @param mixed $limit false or int
     * @return Element[]
     */
    public static function getObjElementsArray($obj, $limit = false)
    {
        $criteria = new Criteria('Arrow\Media\ElementConnection');
        $criteria->addOrderBy(ElementConnection::F_SORT, Criteria::O_ASC);
        $criteria->addCondition(ElementConnection::F_MODEL, $obj["model"]);
        $criteria->addCondition(ElementConnection::F_OBJECT_ID, $obj["ids"], Criteria::C_IN);
        $criteria2 = new Criteria('Arrow\Media\Element');
        $meds = Element::getDataSetByCriteria(new \Arrow\ORM\JoinCriteria(array($criteria2, $criteria)), Element::TCLASS);
        $retm = array();
        foreach ($meds as $m) {
            $retm[$m["ElementConnection:object_id"]][] = $m;
        }
        return $retm;
    }


    /**
     * Returns media elements connected with object
     * @param Model $obj model object
     * @param string $name elements name
     * @return Element[]
     */
    public static function getObjElementsPath($obj, $name = false)
    {
        $nameCondition = "";
        if ($name != false) {
            $nameCondition = "media_element_connections.`name` = '{$name}' AND";
        }

        $q = "
			SELECT media_elements.`path` , media_element_connections.`name`
			FROM media_elements 
			left JOIN media_element_connections ON (media_elements.id=media_element_connections.element_id ) 
			WHERE 
			{$nameCondition}
			media_element_connections.`model` = '{$obj->getModel()}' AND 
			media_element_connections.`object_id` = '{$obj->getPKey()}' 
			ORDER BY media_element_connections.`sort` ASC
			";
        $res = SqlRouter::toArray(SqlRouter::query($q));

        $result = array();
        foreach ($res as $row) {
            $connName = $row["name"] ? $row["name"] : "noname";
            $result[$connName][] = $row["path"];
        }

        return $result;

    }

    public static function getMedia($obj)
    {
        MediaAPI::prepareMedia(array($obj));
        return $obj->getParameter("media");
    }

    public static function prepareMedia($objSet, $namesToGet = null, $limit = null)
    {


        if (empty($objSet)) {
            return false;
        }
        $keys = array();
        $first = isset($objSet[0]) ? $objSet[0] : reset($objSet);

        if (!($first instanceof PersistentObject)) {
            return;
            debug_print_backtrace();
            exit();
        }


        $class = get_class($first);
        $tmp = explode('\\', $class);
        $class = end($tmp);


        $db = \Arrow\Models\Project::getInstance()->getDB();


        foreach ($objSet as $obj) {
            if ($obj) {
                $keys[$obj->getPKey()] = $obj;
                $obj->setParameter(self::MEDIA_VAR, array());
            }
        }


        $connName = "";
        if (is_array($namesToGet)) {
            $connName = "AND media_element_connections.`name` IN ('" . implode("','", $namesToGet) . "')";
        }


        $limitQ = "";
        if ($limit == 1) {
            $limitQ = " group by media_element_connections.`object_id`";
        } elseif ($limit > 1) {
            throw new \Arrow\Exception("[MediaApi] Limits [nand] > x > 1 not implemented");
        }

        $q = "
		SELECT 
		media_element_connections.`id` as 'Conn:id', 
		media_element_connections.`name` AS `Conn:name`, 
		media_element_connections.`object_id` as `Conn:object_id`,  
		media_element_connections.`data` as `Conn:data`,

		media_elements.`id` as `Element:id`,
		media_elements.`name` as `Element:name`,
		media_elements.`path` as `Element:path`

		FROM media_elements
		left JOIN media_element_connections ON (media_elements.id=media_element_connections.element_id ) 
		WHERE
		media_element_connections.`model` like '%" . addslashes($class) . "'
		AND media_element_connections.`object_id` IN ('" . implode("','", array_keys($keys)) . "')
		{$connName} 
		{$limitQ}
		ORDER BY  media_element_connections.`sort` ASC,media_elements.sort ASC";
        //@todo usunąć like i przeyrócić = ( zmieniły się nazwy klas i problem jest )
        //media_element_connections.`model` = '" . addslashes($class) . "'


        $result = $db->query($q);

        // print_r($result);

        $tmp = array();
        foreach ($result as $row) {
            if (!isset($tmp[$row["Conn:object_id"]])) {
                $tmp[$row["Conn:object_id"]][$row["Conn:name"]][$row["Element:id"]] = array();
            }

            $tmp[$row["Conn:object_id"]][$row["Conn:name"]][$row["Element:id"]]["id"] = $row["Element:id"];
            $tmp[$row["Conn:object_id"]][$row["Conn:name"]][$row["Element:id"]]["connection_id"] = $row["Conn:id"];
            $tmp[$row["Conn:object_id"]][$row["Conn:name"]][$row["Element:id"]]["connection_data"] = $row["Conn:data"];
            $tmp[$row["Conn:object_id"]][$row["Conn:name"]][$row["Element:id"]]["name"] = $row["Element:name"];

            if (isset($row["Element:path"])) {
                $tmp[$row["Conn:object_id"]][$row["Conn:name"]][$row["Element:id"]]["path"] = self::$basePath . $row["Element:path"];
            }


        }


        foreach ($tmp as $objId => &$elements) {
            foreach ($elements as $name => $values) {
                $elements[$name] = array_values($elements[$name]);
            }
            //print_r($elements);
            $keys[$objId]->setParameter(self::MEDIA_VAR, $elements);
        }

    }


    public static function prepareObjSetElements($objSet, $name, $arrVersions = self::NONE)
    {
        if (empty($objSet)) {
            return false;
        }
        $keys = array();
        foreach ($objSet as $obj) {
            $keys[$obj->getPKey()] = $obj;
        }
        $criteria = new Criteria("media.ElementConnection");
        $criteria->addOrderBy(ElementConnection::F_SORT, Criteria::O_ASC);
        $criteria->addCondition(ElementConnection::F_MODEL, $objSet[0]->getModel());
        $criteria->addCondition(ElementConnection::F_OBJECT_ID, array_keys($keys), Criteria::C_IN);
        if ($name) {
            $criteria->addCondition(ElementConnection::F_NAME, $name);
        }
        $criteria2 = new Criteria("media.Element");

        $result = Element::getByCriteria(new OrmJoinCriteria(array($criteria2, $criteria)), Element::TCLASS);
        $tmp = array();
        foreach ($result as $element) {
            $conn = $element->getRel("media.ElementConnection");
            if (!isset($conn[0])) {
                continue;
            }
            $connName = $conn[0]["name"] ? $conn[0]["name"] : "noname";
            $objKey = $conn[ElementConnection::F_OBJECT_ID];
            $tmp[$objKey][$connName][] = $element;
        }
        foreach ($tmp as $objId => $elements) {
            $keys[$objId]->setValue(self::MEDIA_VAR, $elements);
        }

        return true;
    }

    public static function prepareObjSetElementsPath($objSet, $name = false, $arrVersions = self::NONE, $limit = false)
    {
        if (empty($objSet)) {
            return false;
        }
        $keys = array();
        foreach ($objSet as $obj) {
            $keys[$obj->getPKey()] = $obj;
        }

        $nameCondition = "";
        if ($name != false) {
            $nameCondition = "media_element_connections.`name` = '{$name}' AND";
        }

        $q = "
			SELECT media_elements.`path` , media_element_connections.`name` , media_element_connections.`object_id`
			FROM media_elements 
			left JOIN media_element_connections ON (media_elements.id=media_element_connections.element_id ) 
			WHERE 
			{$nameCondition}
			media_element_connections.`model` = '{$obj->getModel()}'  
			AND media_element_connections.`object_id` IN ('" . implode("','", array_keys($keys)) . "')
			ORDER BY media_element_connections.`sort` ASC
			";
        $res = SqlRouter::toArray(SqlRouter::query($q));

        $result = array();
        foreach ($res as $row) {
            $connName = $row["name"] ? $row["name"] : "noname";
            $result[$row["object_id"]][$connName][] = $row["path"];
        }

        foreach ($result as $objectId => $elements) {
            $keys[$objectId]->setValue(self::MEDIA_VAR, $elements, true);
        }

        return true;

    }


    public static function copyConnections($sourceObj, $targetObj)
    {
        $criteria = new Criteria();
        $criteria->addCondition(ElementConnection::F_MODEL, $sourceObj->getModel());
        $criteria->addCondition(ElementConnection::F_OBJECT_ID, $sourceObj->getPKey());
        $set = ElementConnection::getByCriteria($criteria, ElementConnection::TCLASS);
        foreach ($set as $conn) {
            $newConn = $conn->copy();
            $newConn[ElementConnection::F_MODEL] = $targetObj->getModel();
            $newConn[ElementConnection::F_OBJECT_ID] = $targetObj->getPKey();
            $newConn->save();
        }
    }

    public static function addObjectElementConnection($object, $element, $connectionName = "noname")
    {
        return ElementConnection::create(array(
            ElementConnection::F_ELEMENT_ID => $element->getPKey(),
            ElementConnection::F_MODEL => $object->getModel(),
            ElementConnection::F_OBJECT_ID => $object->getPKey(),
            ElementConnection::F_NAME => $connectionName
        ), ElementConnection::TCLASS)->save();
    }

    public static function removeFileSystemDir($dir)
    {
        $files = glob($dir . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::removeFileSystemDir($file);
            } else {
                unlink($file);
            }
        }

        if (is_dir($dir)) {
            rmdir($dir);
        }
    }

    public static function synchronize($foldersOnly = true)
    {


        $dirsList = self::synchronizeFolders($folder);

        //self::synchronizeFiles($dirsList);

    }

    public static function getFilesToSynchronize($path, $folderId)
    {
        $files = array();
        $list = glob($path . '/*', GLOB_NOSORT);
        foreach ($list as $file) {
            if (is_file($file)) {
                $files[] = $file;
            }
        }

        $criteria = new Criteria();
        $criteria->addCondition("path", $files, Criteria::C_IN);
        $versions = MediaVersionResult::getByCriteria($criteria, MediaVersionResult::TCLASS);
        foreach ($versions as $version) {
            foreach ($files as $key => $file) {
                if ($file == $version["path"]) {
                    unset($files[$key]);
                }
            }
        }

        $criteria = new Criteria();
        $criteria->addCondition("path", $files, Criteria::C_IN);
        $criteria->addCondition(Element::F_FOLDER_ID, $folderId);
        $elements = Element::getByCriteria($criteria, Element::TCLASS);
        foreach ($elements as $element) {
            foreach ($files as $key => $file) {
                if ($file == $element["path"]) {
                    unset($files[$key]);
                }
                if (basename($file) == "mf_mini" . $element->getPKey() . ".jpg") {
                    unset($files[$key]);
                }
            }
        }

        return $files;
    }

    private static function synchronizeFiles($path)
    {

        //foreach( $dirsList as $path){
        $list = glob($path . '/*', GLOB_NOSORT);
        foreach ($list as $file) {
            if (is_file($file)) {
                print $file . "\n";
            }
        }
        //}


    }

    public static function synchronizeFolders($folder = null)
    {
        if ($folder == null) {
            $folder = self::getRootFolder();
        }

        if (!file_exists($folder->getFilesystemPath(true))) {
            mkdir($folder->getFilesystemPath(true));
        }

        $children = $folder->getAllChildren();

        $dbPaths = array();
        $dbPaths[$folder["id"]] = $folder->getFilesystemPath();
        foreach ($children as $child) {
            $dbPaths[$child["id"]] = $child->getFilesystemPath();
        }

        $root = $folder->getFilesystemPath(true);
        $dirs = self::findDirectiories($root);
        array_unshift($dirs, $root);

        $DbNonfileSys = array_diff($dbPaths, $dirs);
        $fileSysNonDb = array_diff($dirs, $dbPaths);

        foreach ($fileSysNonDb as $dir) {
            self::createFolderFromFileSys($dir, $dbPaths);
        }
        foreach ($DbNonfileSys as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir);
            }
        }

        return $dbPaths;
    }


    private static function createFolderFromFileSys($dir, &$currDbDirs)
    {
        $elements = explode("/", $dir);
        $name = array_pop($elements);
        $parent_id = false;
        foreach ($elements as $key => $el) {
            $tmp = $elements;
            $tmp = array_slice($tmp, 0, $key + 1);
            $path = implode("/", $tmp);
            if ($key > 0) {
                $parent_id = array_search($path, $currDbDirs);
                if ($parent_id == false) {
                    self::createFolderFromFileSys($path, $currDbDirs);
                    continue;
                }
            }
        }

        if ($parent_id == false) {
            throw new \Arrow\Exception(array("msg" => "Synchronization failed", "name" => $name, "dir" => $dir, "dbDirs" => $currDbDirs));
        }


        $data = array(
            "parent_id" => $parent_id,
            "name" => $name
        );
        $node = new Folder($data);
        $currDbDirs[$node->getPKey()] = $dir;
    }

    private static function findDirectiories($path)
    {

        $list = glob($path . '/*', GLOB_ONLYDIR);
        foreach ($list as $file) {
            $list = array_merge($list, self::findDirectiories($file));
        }
        return $list;
    }

    public static function refreshImageCache(Element $path)
    {
        $dir = ARROW_CACHE_PATH . "/img/";
        $info = pathinfo($path["path"]);

        $pattern = "/" . str_replace(".", "-[0-9]+?x[0-9]+?(_crop|)\.", addslashes($info['basename'])) . "/";

        foreach (new \DirectoryIterator($dir) as $file) {
            if (preg_match($pattern, $file->getBaseName())) {
                unlink($file->getPathname());
            }
        }
    }


    public static function getMini($path, $width, $height, $crop = false, $points = false)
    {

        if (!file_exists($path)) {


            $_path = str_replace(self::$basePath, self::$baseURL, $path);

            if (!file_exists($_path)) {
                return $_path;
            }
            //$path = $_path;
        }

        //http://static.esotiq.com/data//System/Arrow_Package_Application_Product/18724-99X.jpg
        ///var/www/static/./data/uploads/System/Arrow_Package_Application_Category/18724-99X-1.jpg

        $dir = ARROW_CACHE_PATH . "/img/";
        $info = pathinfo($path);
        $file = $dir . str_replace(".", "-{$width}x{$height}" . ($crop ? "_crop" : "") . ($points ? implode("_", $points) : "") . ".", $info['basename']);
        //$file = $dir . str_replace(".", "-{$width}x{$height}".($crop?"_crop":"").($points?implode("_",$points):"").".", $info['filename'].".png");
        $file = str_replace(array("(", ")"), array("_", "_"), $file);


        if (!file_exists($file)) {


            $imTransform = new \ImageTransform();
            $imTransform->load($path);
            $imTransform->setTargetFile($file);

            if ($points && isset($points[2])) {
                $imTransform->crop($points[2] - $points[0], $points[3] - $points[1], $points[0], $points[1]);
                $imTransform->load($file);
            }

            $size = getimagesize($path);
            $w = $size[0];
            $h = $size[1];

            if ($crop) {
                $rw = $w / $width;
                $rh = $h / $height;

                if ($rw > $rh) {
                    $imTransform->resizeToHeight = $height;
                } else {
                    $imTransform->resizeToWidth = $width;
                }
            } else {
                $imTransform->resizeToWidth = $width;
                $imTransform->resizeToHeight = $height;

            }

            @$imTransform->resize();

            if ($crop) {
                $imTransform->load($file);
                $imTransform->crop($width, $height);
            }
        }
        return ltrim(str_replace(ARROW_DOCUMENTS_ROOT, "", $file), "/");

    }


    /*



    private static function loadVersions(){
        $tmp = MediaTransformationVersion::getByCriteria(new Criteria(), MediaTransformationVersion::TCLASS);
        foreach($tmp as $r)
            self::$versions[ $r->getPKey() ] = $r[MediaTransformationVersion::F_NAME];
    }

    public static function prepareObjectElements($objSet, $name = false, $prepareVersions = false, $first = false){
        if(empty($objSet))
            return false;
        $tmp = array(-1);
        foreach ($objSet as $key => $o){
            $tmp[$o->getPKey()] = $o;
            $o->setValue(self::MEDIA_VAR, array(), true);
        }

        $model = $objSet[0]->getModel();

        $conCriteria = new Criteria("media.ElementConnection");
        $conCriteria->addOrderBy( ElementConnection::F_SORT, Criteria::O_ASC );
        $conCriteria->addCondition( ElementConnection::F_MODEL, $model );
        $conCriteria->addCondition( ElementConnection::F_OBJECT_ID, array_keys($tmp), Criteria::C_IN );
        if($name)
            $conCriteria->addCondition(ElementConnection::F_NAME, $name);


        if($first){
            //todo
        }

        $elCriteria =  new Criteria("media.Element");
        $elements = Element::getByCriteria( new OrmJoinCriteria(array($elCriteria, $conCriteria)), Element::TCLASS );


        if($prepareVersions)
            self::prepareVersions($elements);

        $bindSet = array();
        foreach($elements as $el){
            $conn = $el->getRel("ElementConnection");
            $objId = $conn[0][ElementConnection::F_OBJECT_ID];
            $connName = $conn[0][ElementConnection::F_NAME];
            if(empty($connName)) $connName = "noname";
            if(!isset($bindSet[$objId])) $bindSet[$objId] = array();
            $bindSet[$objId][$connName][] = $el;
        }
        foreach($bindSet as $objId => $toAssign){
            $tmp[$objId]->setValue(self::MEDIA_VAR, $toAssign, true);
        }
    }

    public static function getObjectElements($obj, $filter = array()){
        $criteria = new Criteria(ElementConnection::TCLASS );
        $criteria->addOrderBy( ElementConnection::F_SORT, Criteria::O_ASC );
        $criteria->addCondition( ElementConnection::F_MODEL, $obj->getModel() );
        $criteria->addCondition( ElementConnection::F_OBJECT_ID, $obj->getPKey() );

        $criteria2 = new Criteria(Element::TCLASS);


        if(!empty($filter)){
            $criteria2->startGroup(Criteria::C_OR_GROUP);
            foreach($filter as $ex)
                $criteria2->addCondition(Element::F_NAME, '%.'.$ex, Criteria::C_ILIKE);
             $criteria2->endGroup();
        }


        return Element::getByCriteria( new OrmJoinCriteria(array($criteria2, $criteria)), Element::TCLASS);
    }



    public static function getObjectElementVersions( $obj, $versionName = false ){

        if(empty(self::$versions))
            self::loadVersions();

        $criteria = new Criteria();
        $criteria->addCondition( ElementConnection::F_MODEL, $obj->getModel() );
        $criteria->addCondition( ElementConnection::F_OBJECT_ID, $obj->getPKey() );
        $conn = ElementConnection::getByCriteria( $criteria, ElementConnection::TCLASS );
        if(empty($conn))
            return false;
        $tmp = array();
        foreach ($conn as $c)
            $tmp[] = $c[ElementConnection::F_ELEMENT_ID];

        $criteria = new Criteria();
        $criteria->addCondition(MediaVersionResult::F_ELEMENT_ID, $tmp, Criteria::C_IN);



        $el = MediaVersionResult::getByCriteria($criteria, MediaVersionResult::TCLASS);

        $versions = array();
        foreach( $el as $key => $e ){
            if($versionName != false){
                if(self::$versions[$e[MediaVersionResult::F_VERSION_ID]] == $versionName){
                    $versions[] = $e->getFilePath();
                }
            }else{

                $versions[$e[MediaVersionResult::F_ELEMENT_ID]][ self::$versions[$e[MediaVersionResult::F_VERSION_ID]]] = $e->getFilePath();
            }
        }

        return array_merge($versions);

    }

    public static function getSystemFolder( $systemName, $name = "" ){
        $criteria = new Criteria();
        $criteria->addCondition(Folder::F_SYSTEM_NAME, $systemName);
        $folder = Folder::getByCriteria($criteria, Folder::TCLASS);
        if( empty($folder) ){
            $folder = self::createFolder( false, $name, $systemName );
        }else{
            $folder = $folder[0];
        }
        return $folder;
    }


    public static function createFolderInSystem( $parentSystemName, $folderName, $systemName ){
        $logicalParent = self::getSystemFolder($parentSystemName, "Container [ {$parentSystemName} ]");
        return self::createFolder($logicalParent->getPKey(), $folderName, $systemName);
    }

    public static function getSystemContainer(){
        $criteria = new Criteria();
        $criteria->addCondition( Folder::F_SYSTEM_NAME, "system.container" );
        $systemContainer = Folder::getByCriteria( $criteria, Folder::TCLASS );
        if(empty($systemContainer)){
            $systemContainer = Folder::create( array(
                Folder::F_PARENT_ID => 1,
                Folder::F_SYSTEM_NAME => "system.container",
                Element::F_NAME => "Zasobnik systemowy",
            ), Folder::TCLASS );
            $systemContainer->save();
        }else{
            $systemContainer = $systemContainer[0];
        }
        return $systemContainer;
    }


    */

}

?>
