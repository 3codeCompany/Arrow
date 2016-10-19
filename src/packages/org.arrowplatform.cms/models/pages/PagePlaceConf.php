<?php
namespace Arrow\Package\CMS;


use \Arrow\ORM\Criteria;

class PagePlaceConf extends \Arrow\ORM\ORM_Arrow_Package_CMS_PagePlaceConf{

	/**
	 * List of all places defined in system
	 */
	protected static $places_list = null;
	

	public function getName(){
		if (self::$places_list === null){
            self::$places_list = Criteria::query('Arrow\Package\CMS\PagePlace')->find();
	 	}
		foreach (self::$places_list as $place){
			if ($this[self::F_PLACE_ID] == $place[PagePlace::F_ID])
				return $place[PagePlace::F_NAME];
		}
		return null;
	}

	public function getType(){
		if (self::$places_list === null){
            self::$places_list = Criteria::query('Arrow\Package\CMS\PagePlace')->find();
	 	}
		foreach (self::$places_list as $place){
			if ($this[self::F_PLACE_ID] == $place[PagePlace::F_ID])
				return $place[PagePlace::F_TYPE];
		}
		return null;
	}
	 
	public function getModules(){
		if(empty($this[self::F_MODULES]))
			return array();
		$tmp = explode( ";", $this[self::F_MODULES] );
		$result =  Criteria::query('Arrow\Package\CMS\Module')->c(Module::F_ID, $tmp, Criteria::C_IN )->find();
		
		foreach ( $tmp as $key => $val ){
			foreach ($result as $module)
				if( $module[Module::F_ID] == $val )
					$tmp[$key] = $module;
		}
		return $tmp;
	}
	


	//*END OF USER AREA*//
}
?>