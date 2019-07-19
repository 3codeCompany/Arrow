<?php
/**
 * Created by PhpStorm.
 * User: artur.kmera
 * Date: 22.06.2018
 * Time: 15:09
 */

namespace Arrow\Common\Models\Dictionaries;


use Arrow\Exception;
use Arrow\ORM\Extensions\TreeNode;
use Arrow\ORM\ORM_Arrow_Common_Models_Dictionaries_Dictionary;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\Persistent\PersistentObject;

class Dictionary extends ORM_Arrow_Common_Models_Dictionaries_Dictionary
{
    use TreeNode;

    public static function getDictionaryFor($name, $system = true)
    {
        $parent = Dictionary::get()
            ->_systemName($name)
            ->findFirst();

        if (!$parent) {
            throw new \Exception("No dictionary '{$name}' found");
        }

        return $parent->getChildren()->map(function (Dictionary $dic) {
            return
                [
                    "value" => $dic->_id(),
                    "label" => $dic->_label(),
                    "additional_value" => $dic->_value(),
                    "additional_data" => $dic->_data(),
                    "system_name" => $dic->_systemName()
                ];

        });
    }

    public static function getSystemDictionaryFor($name, $key = Dictionary::F_ID) {

        $dictionary = Dictionary::get()
            ->_join(Dictionary::getClass(), [Dictionary::F_PARENT_ID => Dictionary::F_ID], 'Parent', [Dictionary::F_LABEL])
            ->c("Parent:system_name", $name)
            ->find();

        $result = [];
        foreach($dictionary as $item) {
            $result[$item[$key]] = [
                Dictionary::F_ID => $item[Dictionary::F_ID],
                Dictionary::F_SYSTEM_NAME => $item[Dictionary::F_SYSTEM_NAME],
                Dictionary::F_LABEL => $item[Dictionary::F_LABEL]
            ];
        }

        return $result;
    }



}