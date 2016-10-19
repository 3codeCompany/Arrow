<?php
namespace Arrow\Package\Common;
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 27.08.12
 * Time: 21:13
 * To change this template use File | Settings | File Templates.
 */
class ORMObjectsArchiver extends \Arrow\ORM\BaseTracker
{
    private static $oInstance = null;

    public static function getTracker($class)
    {
        if(self::$oInstance == null)
            self::$oInstance = new ORMObjectsArchiver();
        return self::$oInstance;
    }

    public function afterObjectDelete(\Arrow\ORM\PersistentObject $object)
    {
        $archive = new Archive(array(
           Archive::F_CLASS => $object::getClass(),
           Archive::F_DATA => serialize($object->getData()),
           Archive::F_OBJECT_ID => $object->getPKey()
        ));
        $archive->save();
    }

}
