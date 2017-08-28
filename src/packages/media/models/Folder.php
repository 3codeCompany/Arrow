<?php
namespace Arrow\Media\Models;

use Arrow\ORM\Extensions\TreeNode;
use Arrow\ORM\ORM_Arrow_Media_Models_Folder;
use Arrow\ORM\Persistent\PersistentObject;

class Folder extends ORM_Arrow_Media_Models_Folder
{

    use TreeNode;

    public function beforeObjectCreate(PersistentObject $object)
    {
        $dirName = \Arrow\Utils\StringHelper::toValidFilesystemName($this->data[self::F_NAME], false);
        $parent = \Arrow\ORM\Persistent\Criteria::query(self::getClass())->findByKey($this->data[self::F_PARENT_ID]);
        $path = $parent[self::F_PATH] . "/" . str_replace("\\", "_", $dirName);

        $created = @mkdir($path, 0777, true);

        if (!$created && !file_exists($path)) {
            throw  new \Arrow\Exception(array(
                "msg" => "Can't create directory.",
                "path" => $path
            ));
        }

        $this->data[self::F_PATH] = $path;
        parent::beforeObjectCreate($object);
    }

    public function fieldModified(PersistentObject $object, $field, $oldValue, $newValue)
    {
        if (($field == self::F_PARENT_ID || $field == self::F_NAME) && $object->getPKey()) {
            $this->updatePath();
        }
    }

    public function afterObjectCreate(PersistentObject $object)
    {
        parent::afterObjectCreate($object);
        $this->updatePath();
    }


    public function getValue($field)
    {
        if ($field == self::F_PATH && $this->data[self::F_PARENT_ID] == 0) {
            return ARROW_UPLOADS_PATH;
        }
        return parent::getValue($field);
    }

    public function updatePath()
    {
        $dirname = \Arrow\Utils\StringHelper::toValidFilesystemName($this->data[self::F_NAME], false);
        $parent = $this->getParent();
        $path = $parent->getValue("path") . "/" . $dirname;
        $dbPath = $this["path"];
        if ($path != $dbPath) {
            if (!file_exists($path)) {
                $renamed = rename($dbPath, $path);
                if (!$renamed) {
                    throw  new \Arrow\Exception(array(
                        "msg" => "Can't change directory path.",
                        "oldPath" => $dbPath,
                        "targetPath" => $path
                    ));
                }
            }

            $this->data[self::F_PATH] = $this->getParent()->getValue(self::F_PATH) . "/" . $dirname;
            $this->save();

            foreach ($this->getChildren() as $child) {
                $child->updatePath();
            }
        }

        /*
          $old = mysql_escape_string($oldPath);
          $new = mysql_escape_string($newPath);
          $q = "update media_elements set path=REPLACE(path,'{$old}','$new') where folder_id in( ".implode(",",$ids).")";
          SqlRouter::query($q);

          $q = "update media_version_result set path=REPLACE(path,'{$old}','$new') where element_id in ( select id from media_elements where folder_id in( ".implode(",",$ids)."))";
          SqlRouter::query($q);
          }*/

    }


    public function delete()
    {

        foreach ($this->getChildren() as $child) {
            $child->delete();
        }

        $elements = $this->getRelated("MediaElement");
        foreach ($elements as $element) {
            $element->delete();
        }

        $trname = trim($this["name"]);
        if (!empty($trname)) {
            MediaApi::removeFileSystemDir($this->getFilesystemPath());
        }
        parent::delete();
    }

    //*END OF USER AREA*//
}

?>
