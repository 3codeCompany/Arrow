<?php
namespace Arrow\Package\Utils;

use Arrow\ORM\Persistent\Criteria;

class UtilsDictionary extends \Arrow\ORM\ORM_Arrow_Package_Utils_UtilsDictionary{



	const MODE_VALUE = "value" ;
	const MODE_CLEAR = "" ;

    /*
	public static function create( $initialValues, $class=self::TCLASS ){
		$criteria = new Criteria();
		$criteria->addCondition(self::F_NAME,$initialValues[self::F_NAME]);
		if(isset($initialValues[self::F_VALUE]))
			$criteria->addCondition(self::F_VALUE,$initialValues[self::F_VALUE]);
		$criteria->addCondition(self::F_PARENT_ID, $initialValues[self::F_PARENT_ID] );
		$result = self::getByCriteria($criteria,self::TCLASS);
		//if(!empty($result))
			//Interaction::error("Podana wartość już istnieje w słowniku","Błąd");
		
		if( !isset( $initialValues["_state"] ) ) {
			$initialValues["_state"] = 0 ;
		}
			
		$object = parent::create($initialValues, $class);
		return $object;
	}
    */

	public function save() {
		if( !isset($this["id"]) && !isset($this["system_name"])  ) {
				$this["system_name"] = StringHelper::toRewrite( $this["name"] ) ;
		}
		parent::save();
	}


	public static function getDictionaryValue( $id ) {

        $ob = Criteria::query(UtilsDictionary::getClass())->findByKey($id);
		if( isset($ob["value"]) )
			return $ob["value"] ;
		return "none value" ;
	}
	
	public static function getDictionaryName( $id ) {
        $ob = Criteria::query(UtilsDictionary::getClass())->findByKey($id);

		if( isset($ob["name"]) )
			return $ob["name"] ;
		return "none name" ;
	}
	
	public static function getDictionaryId( $system_name, $parent_system_name = "" ) {
		$dict = self::getDictionary( $system_name, $parent_system_name );
		return $dict["id"] ;
	}

	public static function getDictionary( $system_name, $parent_system_name = "", $create_if_not_exist = false ) {
		$pdict = null ;
		if( $parent_system_name != "" ) {
			$pcrit = Criteria::query(UtilsDictionary::getClass());
			$pcrit->addCondition( UtilsDictionary::F_SYSTEM_NAME, $parent_system_name ) ;
			$pdict = UtilsDictionary::getByCriteria( $pcrit, UtilsDictionary::TCLASS );
			if( count( $pdict ) > 1 ) throw new \Arrow\Exception(array("msg" =>"Utils Dictionary: Istnieje więcej wpisów\n niż jeden dla nazwy systemowej $system_name " ));
		}
		$crit = Criteria::query(UtilsDictionary::getClass());
		$crit->addCondition( UtilsDictionary::F_SYSTEM_NAME, $system_name ) ;
		if( $pdict != null ) 
			$crit->addCondition( UtilsDictionary::F_PARENT_ID, $pdict[0]["id"] ) ;
		$dict = $crit->find();
		if( count( $dict ) > 1 ) throw new \Arrow\Exception(array("msg" => "Utils Dictionary: Istnieje więcej wpisów\n niż jeden dla nazwy systemowej $system_name " ));
		if( !isset($dict[0]) ) {
			if( $create_if_not_exist && $pdict != null ) {
				$init_val = array(  UtilsDictionary::F_PARENT_ID => $pdict[self::F_PARENT_ID], UtilsDictionary::F_NAME => strtoupper( $system_name ), UtilsDictionary::F_SYSTEM_NAME => $system_name );
				$dict = UtilsDictionary::create( $init_val, UtilsDictionary::TCLASS ) ;
				$dict->save();
				return $dict ;
			} else throw new \Arrow\Exception(array("msg" => "Brak słownika $parent_system_name => $system_name" ));
		}
		return $dict[0] ;
	}

	// from IDelete interface
	public function clean() {
		if( $this[self::F_PARENT_ID] == 1 && !$this->hasChildren() ) {   // jest to główna kategria i nie ma dzieci to usuń
			parent::delete();
			return null;
		} 
		return $this ;
	}



//*END OF USER AREA*//
}
?>