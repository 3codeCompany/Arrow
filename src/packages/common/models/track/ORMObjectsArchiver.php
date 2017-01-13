<?php
namespace Arrow\Common;
use Arrow\ORM\Extensions\BaseTracker;
use Arrow\ORM\Persistent\PersistentObject;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 27.08.12
 * Time: 21:13
 * To change this template use File | Settings | File Templates.
 */
class ORMObjectsArchiver extends BaseTracker
{
    private static $oInstance = null;

    public static function getTracker($class)
    {
        if(self::$oInstance == null)
            self::$oInstance = new ORMObjectsArchiver();
        return self::$oInstance;
    }

    public function afterObjectDelete(PersistentObject $object)
    {
        $archive = new Archive(array(
           Archive::F_CLASS => $object::getClass(),
           Archive::F_DATA => serialize($object->getData()),
           Archive::F_OBJECT_ID => $object->getPKey()
        ));
        $archive->save();
    }

}
