<?php
namespace Arrow\CMS\Models;

use Arrow\Translations\Models\IMultilangObject;

class News extends \Arrow\ORM\ORM_Arrow_CMS_News implements IMultilangObject
{
    function __construct($data = null, $paramerers = array())
    {
        if(empty($data["date2"]) )
            unset($data["date2"]);
        return parent::__construct($data, $paramerers);
    }


    public static function getMultiLangFields()
    {
        return [self::F_TITLE, self::F_CONTENT_SHORT, self::F_CONTENT];
    }


    public function beforeObjectCreate(\Arrow\ORM\PersistentObject $object)
    {
        $this['added'] = date("Y-m-d");
        parent::beforeObjectCreate($object);
    }
}
?>