<?php
namespace Arrow\Access\Models;
use Arrow\ORM\ORM_Arrow_Access_Models_AccessPointGroup;


/**
 * Arrow access group class
 * 
 * @version 1.0
 * @license  GNU GPL
 * @author 3code Team 
 */
class AccessPointGroup extends ORM_Arrow_Access_Models_AccessPointGroup  {

    const TCLASS = __CLASS__;

    public static function create( $params, $class=self::TCLASS ){
        $object = parent::create($params, $class);


        return $object;
    }
	
}
?>