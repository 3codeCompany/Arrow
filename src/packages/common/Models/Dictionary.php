<?php
/**
 * Created by PhpStorm.
 * User: artur.kmera
 * Date: 22.06.2018
 * Time: 15:09
 */

namespace Arrow\Common\Models;


use Arrow\ORM\Extensions\TreeNode;
use Arrow\ORM\ORM_Arrow_Common_Models_Dictionary;

class Dictionary extends ORM_Arrow_Common_Models_Dictionary
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

        return $parent->getChildren();
    }
}