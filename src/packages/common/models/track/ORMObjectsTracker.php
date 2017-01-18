<?php
namespace Arrow\Common\Models\Track;
use Arrow\ORM\Extensions\BaseTracker;
use Arrow\ORM\Persistent\PersistentObject;


/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 27.08.12
 * Time: 21:13
 * To change this template use File | Settings | File Templates.
 */
class ORMObjectsTracker extends BaseTracker
{
    private static $oInstance = null;

    public static function getTracker($class)
    {
        if(self::$oInstance == null)
            self::$oInstance = new ORMObjectsTracker();
        return self::$oInstance;
    }

    private function prepareTrack(PersistentObject $object, $action){

        //Whats changed
        $info = "";
        foreach($object->getChangedData() as $field=>$oldValue){
            if(is_string($oldValue)){
                $old = substr( strip_tags($oldValue), 0,50);
                $new = substr( strip_tags($object->getValue($field)), 0,50);
                $info.= $field.": '".$old."' => '".$new."'\n";
            }
        }
        //creating track object
        $track = new Track(array(
            Track::F_CLASS => $object::getClass(),
            Track::F_ACTION => $action,
            Track::F_OBJECT_ID => $object->getPKey(),
            Track::F_INFO => $info
        ));
        $track->save();
    }

    public function afterObjectSave(PersistentObject $object)
    {
        $this->prepareTrack($object, "modified");
    }

    public function afterObjectCreate(PersistentObject $object)
    {
        $this->prepareTrack($object, "created");
    }

    public function afterObjectDelete(PersistentObject $object)
    {
        $this->prepareTrack($object, "deleted");
    }
}
