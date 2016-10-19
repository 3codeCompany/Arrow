<?php
namespace Arrow\Package\Utils;




class Dictionary extends \Arrow\Package\Database\ProjectPersistent implements IDelete {

	const TCLASS = __CLASS__;
	const F_ID = "id";
	const F_ID_CATEGORY = "id_category";
	const F_VALUE = "value";
	const F_SORT_POSITION = "sort_position";
	const F_SYSTEM_NAME = "system_name";
	const F_NAME = "name";
	const F__STATE = "_state";

	//*USER AREA*//

	const MODE_VALUE = "value" ;
	const MODE_CLEAR = "" ;

	//TODO: wykorzystać sort position do ustawiania kolejnosci wierszy w slowniku - obecnie nie używane

	public static function create( $initialValues, $class=self::TCLASS ){
		$object = parent::create($initialValues, $class);
		
		$criteria = new Criteria();
		$criteria->addCondition(self::F_NAME,$initialValues[self::F_NAME]);
		$criteria->addCondition(self::F_ID_CATEGORY,$initialValues[self::F_ID_CATEGORY]);
		$result = self::getByCriteria($criteria,self::TCLASS);
		if(!empty($result))
			Interaction::error("Podana wartość już istnieje w słowniku","Błąd");
		
		
		return $object;
	}

	/**
	 * Zwraca wszystkie słowniki posortowane alfabetycznie
	 * @return Array of Dictionary
	 */
	public static function getAllDictionaries() {
		$crit = new Criteria();
		//pobierz słownik z najniższym id (główna kategoria)
		$crit->setLimit(0,1);
		$crit->addOrderBy('id',Criteria::O_ASC);
		$category = Dictionary::getByCriteria( $crit, Dictionary::TCLASS );
		if (!empty($category))
			$category_id = $category[0]['id'];
		else 
			return array();	 
		//pobierz wszystkie
		$crit = new Criteria();
		$crit->addCondition( self::F_ID_CATEGORY, $category_id );
		$crit->addCondition( self::F_ID, $category_id, Criteria::C_NOT_EQUAL );
		$crit->addOrderBy( self::F_NAME, Criteria::O_ASC ) ;
		return Dictionary::getByCriteria( $crit, Dictionary::TCLASS );
	}

	/**
	 * Zwraca wszystkie dzieci słownika posortowane alfabetycznie
	 * @return Array Dictionary
	 */
	public function getChildren() {
		$crit = new Criteria();
		$crit->addCondition( self::F_ID_CATEGORY, $this["id"] );
		$crit->addOrderBy( "sort", Criteria::O_ASC ) ;
		$crit->addOrderBy( self::F_NAME, Criteria::O_ASC ) ;
		return Dictionary::getByCriteria( $crit, Dictionary::TCLASS );
	}

	public function save() {
		if( !isset($this["system_name"]) ) {
			$this["system_name"] = StringHelper::toRewrite( $this["name"] ) ;
		}
		parent::save();
		if( $this["sort"] == 0 ) {
			$this["sort"] = $this["id"];
			$this->save();
		}
	}


	public static function getDictionaryValue( $id ) {
		$ob = Dictionary::getByKey( $id, Dictionary::TCLASS );
		if( isset($ob["value"]) )
			return $ob["value"] ;
		return "none value" ;
	}
	
	public static function getDictionaryName( $id ) {
		$ob = Dictionary::getByKey( $id, Dictionary::TCLASS );
		if( isset($ob["name"]) )
			return $ob["name"] ;
		return "none name" ;
	}
	

	public static function getDictionary( $key_name ) {
		$crit = new Criteria() ;
		$crit->addCondition( Dictionary::F_SYSTEM_NAME, $key_name ) ;
		$dict = Dictionary::getByCriteria( $crit, Dictionary::TCLASS );
		return $dict[0] ;
	}

	// from IDelete interface
	public function clean() {
		if( $this[self::F_ID_CATEGORY] == 0 && count( $this->getChildren() == 0 ) ) {   // jest to główna kategria i nie ma dzieci to usuń
			parent::delete();
			return null;
		} 
		return $this ;
	}

	//*END OF USER AREA*//
}
?>