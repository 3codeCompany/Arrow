<?php
namespace Arrow\Package\Access;


/**
 * Arrow access group class
 * 
 * @version 1.0
 * @license  GNU GPL
 * @author 3code Team 
 */
class AccessPointGroup extends \Arrow\ORM\ORM_Arrow_Package_Access_AccessPointGroup  {

    const TCLASS = __CLASS__;

    public static function create( $params, $class=self::TCLASS ){
        $object = parent::create($params, $class);


        return $object;
    }
	
}
?>