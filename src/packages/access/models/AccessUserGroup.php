<?php
namespace Arrow\Access\Models;



/**
 * Arrow access group class
 * 
 * @version 1.0
 * @license  GNU GPL
 * @author 3code Team 
 */
class AccessUserGroup extends \Arrow\ORM\ORM_Arrow_Access_AccessUserGroup  {

    const TCLASS = __CLASS__;

    public static function create( $params, $class=self::TCLASS ){
        $object = parent::create($params, $class);


        return $object;
    }
	
}
?>