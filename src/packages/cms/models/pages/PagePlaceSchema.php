<?php
namespace Arrow\CMS;

class PagePlaceSchema extends \Arrow\ORM\ORM_Arrow_Package_CMS_PagePlaceSchema{

	const M_OVERRIDE = 0; //modules from PagePlace will override modules from SchemaPlace
	const M_ADD = 1; //modules from PagePlace will be added after modules form SchemaPlace
	const M_IGNORE = 2; // Use modules from SchemaPlace even 
	
	protected static $places_list = null;


	public function getName(){
		if (self::$places_list === null){
			$criteria = new Criteria( );
			self::$places_list = PagePlace::getByCriteria( $criteria, PagePlace::TCLASS );
	 	}
		foreach (self::$places_list as $place){
			if ($this[self::F_PLACE_ID] == $place[PagePlace::F_ID])
				return $place[PagePlace::F_NAME];
		}
		return null;
	}

	public function getType(){
		if (self::$places_list === null){
			$criteria = new Criteria( );
			self::$places_list = PagePlace::getByCriteria( $criteria, PagePlace::TCLASS );
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
		$criteria = new Criteria();
		$criteria->addCondition( Module::F_ID, $tmp, Criteria::C_IN );
		$result = Module::getByCriteria($criteria, Module::TCLASS);
		
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