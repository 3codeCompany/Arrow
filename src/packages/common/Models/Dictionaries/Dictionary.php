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

    public static function getDictionaryFor($name)
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
                    "additional_data" => $dic->_data()
                ];

        });
    }


}