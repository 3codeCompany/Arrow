<?php


namespace Arrow\Package\Langs;


class LangVirtualData extends Model implements IStandardController, IControlable  {
	
	const T_CLASS = __CLASS__ ;
	
	private $template = array() ;
	
	public function getTranslatePosition() {
		if( strpos( $this["position"], 't') === 0 ) {
			$pos = str_replace('t', '', $this["position"]);
			if( isset( $this->template[$pos] ) ) return $this->template[$pos] ;
			else {
				$cont = \Arrow\Controller::$project ;
				$ts = TemplatesStructure::getDefault( $cont ) ;
				$tmp = $ts->getTemplateById( $pos ) ;
				/*echo "<pre>" ;
				print_r( $tmp );
				exit;*/
				$desc = $tmp["path"].'::<b>'.$tmp["name"].'</b> ['.$tmp["id"].']';
				$this->template[$pos] = $desc ;
				return $desc ;
			}
		}  
		return $this["position"] ; 
	}
	
	public static function create($initialValues, $class = '' ) {
		$ob = new LangVirtualData() ;
		$ob->data = $initialValues ;
		return $ob ;
	}
	
	public static function getByCriteria( $criteria, $class ){

		if( $criteria->isAggregated() ) {
			$crit = new Criteria( "utils.langs.LangData" ) ;
			$crit->addColumn( LangData::F_ID,  'il', Criteria::A_COUNT );
			$crit->setLimit( 0, 1 );
			$crit->addGroupBy(LangData::F_ID_LANG );
			$cnt = LangData::getByCriteria( $crit, LangData::TCLASS );
			return array( array( "id" => $cnt[0]["il"]  ) );
		}
		// -------------- dotworzenie pozostałych danych językowych
		$crit = new Criteria() ;
		$crit->addOrderBy("name", "ASC");
		$trans = LangData::getByCriteria( $crit, LangData::TCLASS );
		
		$ret = array ();
		$i = 1 ;
		foreach( $trans as $t ) {
			$ret[ $t["name"].'_'.$t['position'] ]["id_{$t['id_lang']}"] = $t['id'] ;
			$ret[ $t["name"].'_'.$t['position'] ]["value_{$t['id_lang']}"] = $t['value'] ;
			$ret[ $t["name"].'_'.$t['position'] ]["name"] = $t["name"] ;
			$ret[ $t["name"].'_'.$t['position'] ]["position"] = $t["position"];
			$ret[ $t["name"].'_'.$t['position'] ]["id"] = $i ;
			++$i;
		}
		
		// dotworzenie wszysktich języków
		$langs = Lang::getAllLangs() ;
		foreach( $langs as $l ) {
			foreach( $ret as $r ) {
				if( !isset( $r["id_{$l["name"]}"] ) ) {  // dotwórz wpis dla języka
					$init = array() ;
					$init[ 'value' ] = '' ;
					$init["name"] = $r["name"] ;
					$init["position"] = $r["position"];
					$init["id_lang"] = $l["name"];
					$t = LangData::create($init, LangData::TCLASS);
					$t->save();
					$ret[ $t["name"].'_'.$t['position'] ]["id_{$t['id_lang']}"] = $t['id'] ;
					$ret[ $t["name"].'_'.$t['position'] ]["value_{$t['id_lang']}"] = $t['value'] ;
				}
			}
		}
		
		
		$datac = $criteria->getData();
		$cntl = count( $langs );
		/*echo "<pre>";
		print_r($datac);
		exit;*/
		//----- do zwrócenia danych
		$crit = new Criteria() ;
		if( isset( $datac["limit"][0] ) ) $crit->setLimit($datac["limit"][0] * $cntl, $datac["limit"][1] * $cntl ) ;
		if( isset( $datac["order"][0] ) ) {
			foreach( $datac["order"] as $ord ) $crit->addOrderBy( $ord[0], $ord[1], $ord[2] ) ;
		}
		
		$trans = LangData::getByCriteria( $crit, LangData::TCLASS );
		$ret = array ();
		$i = 1 ;
		foreach( $trans as $t ) {
			$ret[ $t["name"].'_'.$t['position'] ]["id_{$t['id_lang']}"] = $t['id'] ;
			$ret[ $t["name"].'_'.$t['position'] ]["value_{$t['id_lang']}"] = $t['value'] ;
			$ret[ $t["name"].'_'.$t['position'] ]["name"] = $t["name"] ;
			$ret[ $t["name"].'_'.$t['position'] ]["position"] = $t["position"];
			$ret[ $t["name"].'_'.$t['position'] ]["id"] = $i ;
			++$i;
		}
		
		
		
		$rob = array() ;
		foreach( $ret as $r ) {
			$rob[] = self::create( $r, self::T_CLASS );
		}
		return $rob ;
		
	} 

	public function setValue( $field, $value, $tmp = false) {
		parent::setValue($field, $value, true );
	}
	
	public function save() {
		throw new \Arrow\Exception("Nie można zapisywać tego obiektu");
	}
	
	public static function getByKey( $key, $class ) { ; } 
	
	
	public function delete() { ; } 
	
	public function getPKey() { return $this->data['id'] ; } 
	
	public function setValues( $values ) { ;	}
	
	public function getValue( $field ) {
		/*print_r( $this );
		exit;*/
		return $this->data[$field];
	
	}
	
    //--------------------- Iarrowcontrolable
    public static function getActions( $model, $type = null ) {
    	return null ;
    
    }
    
    // ------------------------IModel
    public function getModel() {
    	return "utils.langs.LangVirtualData" ;	
    
    }
}
?>