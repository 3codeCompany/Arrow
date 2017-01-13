<?php
namespace Arrow\Translationss;



class LangData extends \Arrow\Package\Database\ProjectPersistent{

	const TCLASS = __CLASS__;
	const F_ID = "id";
	const F_NAME = "name";
	const F_VALUE = "value";
	const F_POSITION = "position";
	const F_ID_LANG = "id_lang";


//*USER AREA*//

	private static $obj = null;
	private static $cache = array() ;		// skeszowane zmienne
	private static $mode =  0;				// mode

	private static $show_mode = true ;
	
	private static $current_lang_id = 0 ; 
	private static $edit_img = "" ;
	
	private static $words_from_page = array() ;
	
	public static function create( $initialValues, $class=self::TCLASS ){
		$object = parent::create($initialValues, $class);
		return $object;
	}

	public static function getDefault( $l ) {
		if( self::$obj == null ) {
			self::$obj = new LangData( array("id"=>-1) );
			//$cr = Lang::getCurrentLang();
			//print_r($l);
		//	exit;
			self::$current_lang_id = $l["name"];
			$p = \Arrow\Controller::$project->getPath() ;
			self::$edit_img = $p."/resources/graphic/administration/icons/edit_lang.png" ;
			//self::$obj->loadCache();
		}	
		return self::$obj ;
	}

	public function turnOnEditMode() {
		self::$show_mode = true;
	}
	
	public function turnOffEditMode() {
		self::$show_mode = false;
	}
	
	public function getLangName() {
		$l = Lang::getLangName($this["id_lang"]);
		return $l;
	}
	
	/**
	 * Returns current lang (pl, en...)
	 */
	/*public static function getCurrentLangId() {
		return self::$current_lang_id ;
	}*/
	
	public function getValue( $field ) {
	  if($this == self::$obj ) {   // menadzer językowy
	  	   if( $field == "global" ) {
		   	  self::$mode = 1 ;
		   	  return $this;	
		   }
		   $where = "";
		   if( self::$mode == 1 ) { // są to globalne wartośći
		   	 self::$mode = 0 ;
		   	 $where = "global" ;
		   	 //if( isset($this->) )
		   	 //return "ToDo: global " ;
		   } else $where = $this->whereAmI();
		   	
		   	$val = "" ;
		   	
		   	if( !array_key_exists( $where, self::$cache) )    //jeśli nie ma załaduj tą przestrzeń
		   		$this->loadPosition( $where, self::$current_lang_id ) ;
		   	
		   		if( !array_key_exists( $field, self::$cache[$where] ) ) {
		   			$data = array();
		   			$data["position"] = $where ;
		   			$data["name"] = $field ;
		   			$data["id_lang"] = self::$current_lang_id ;
		   			$objl = LangData::create( $data ) ;
		   			$objl->save();
		   			self::$cache[$where][$field] = "" ;
		   		} else $val = self::$cache[$where][$field] ; 
	  			
		   		if( self::$show_mode ) {
		   			if( !isset( self::$words_from_page[ $where ] ) ) self::$words_from_page[ $where ] = array();
		   			self::$words_from_page[ $where ][$field] = $val ;
		   		}	
		   		if( $val == "" ) $val = "<span class=\"important\" >[$field]</span>" ; 
		   		
		   		//}//$val = "<div style=\"display:inline;\" >$val<a class=\"modal\" href=\"index.php?template=56&where=$where&field=$field\" ><img style=\"width:20px;\" src=\"".self::$edit_img."\" /></a></div>" ;
				return $val ;
		   
	  }	else {
	  	return parent::getValue( $field );
	  }
		
	   
	   
	   
	}
	
	private function loadPosition( $where, $id_lang ) {
		$crit = new Criteria() ;   		
		$crit->addCondition( LangData::F_POSITION, $where ) ;
		$crit->addCondition( LangData::F_ID_LANG, $id_lang ) ;
		$nd = LangData::getByCriteria( $crit, LangData::TCLASS );
		self::$cache[ $where ] = array() ;
		foreach( $nd as $n ) {
			self::$cache[ $where ][$n["name"]] = $n["value"] ;
		}
	}
	
	private function whereAmI() {
		//echo "<pre>" ;
		$view =  View::getCurrentView();
		$position = "" ;
		if( !empty($view) ) {
			$template_id = View::getCurrentView()->getTemplateDescriptor()->getId();
			//print_r($template);
		  	$position = "t".$template_id ; 	
		} else {
			$req = RequestContext::getDefault();
			if( isset($req["actionBean"]) ) {
				$possition = "ab".$req["actionBean"] ;
			} else {
				throw new \Arrow\Exception( "[LangData] I don't know Where I am ??" ) ;
			}
		}
		
		return $position;
		
		//exit;
	}
	
	public function valueExists( $field ) {
		if( $this == self::$obj ) {
			print_r( "czekam" );
			exit;
			throw new \Arrow\Exception( "TODO: LangData static value Exists" ) ;
		} else 
			return parent::valueExists( $field );
	}
	
	public function setValue( $field, $value, $tmp=false ) {
		if( $this == self::$obj )
			throw new Exception( "[LangData::offsetSet]:deny" );
		else parent::setValue( $field, $value, $tmp ) ;
	}
	
	public function unsetValue( $field ) {
		if( $this == self::$obj )
			throw new \Arrow\Exception(array("msg"=>"[LangData::unsetValue]:deny"));
		else parent::unsetValue( $field );
	}
	
	/**
	 * Returns all langs use on the Page
	 * @return - current langs array( 0 => array global langs, 1 => array page langs )
	 */
	/*public static function getLangsOnThisPage() {
		return self::$words_from_page ;
	}*/

	public static function packData() {
		
		$_SESSION["arrow"]["langs"]["clangs"] = self::$words_from_page ;
	}
	
	public static function unPackData() {
		$data = $_SESSION["arrow"]["langs"]["clangs"] ;
		FB::log($_SESSION["arrow"]["langs"]);
		unset($_SESSION["arrow"]["langs"]["clangs"]) ;
		return  $data ;
	
	}

//*END OF USER AREA*//
}
?>