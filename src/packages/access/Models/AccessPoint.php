<?php
namespace Arrow\Access\Models;

use Arrow\ORM\ORM_Arrow_Access_Models_AccessPoint;
use Arrow\ORM\Persistent\PersistentObject;


/**
 * Arrow access group class
 * 
 * @version 1.0
 * @license  GNU GPL
 * @author 3code Team 
 */
class AccessPoint extends ORM_Arrow_Access_Models_AccessPoint  {
    public function afterObjectSave(PersistentObject $object)
    {
        parent::afterObjectSave($object);
        //AccessAPI::saveAccessMatrixToPackages($this[AccessPoint::F_POINT_NAMESPACE]);
    }


}
?>