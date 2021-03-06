<?php
namespace Arrow\Common\Models\Track;
use Arrow\ORM\ORM_Arrow_Common_Models_Track_Track;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 27.08.12
 * Time: 21:59
 * To change this template use File | Settings | File Templates.
 */
class Track extends ORM_Arrow_Common_Models_Track_Track
{
    public function __construct($initialData = null){
        $initialData[self::F_DATE] = date( "Y-m-d H:i:s" );
        if(!isset($initialData[self::F_USER_ID])){
            if( \Arrow\Access\Models\Auth::getDefault()->isLogged())
                $initialData[self::F_USER_ID] = \Arrow\Access\Models\Auth::getDefault()->getUser()->getPKey();
            else
                $initialData[self::F_USER_ID] = -1;
        }
        parent::__construct($initialData);
    }

    public static function celarOlderThan( \DateInterval $interval ){
        $db = \Arrow\Models\Project::getInstance()->getDB();
        $now = new \DateTime();
        $now->sub($interval);
        $db->exec("DELETE FROM ".self::getTable()." WHERE ".self::F_DATE." < '".$now->format("Y-m-d")."'" );
    }

}
