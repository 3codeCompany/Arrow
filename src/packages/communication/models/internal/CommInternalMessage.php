<?php



class CommInternalMessage extends \Arrow\Package\Database\ProjectPersistent{

	const TCLASS = __CLASS__;
	const F_ID = "id";
	const F_TOPIC = "topic";
	const F_CONTENT = "content";
	const F_ID_PARENT = "id_parent";
	const F_ID_USER_FROM = "id_user_from";
	const F_ID_USER_TO = "id_user_to";
	const F_STATE = "state";
	const F_DATE_CREATE = "date_create";


//*USER AREA*//
	// state 0- nieprzeczytana 1- przeczytana
	const STATE_UNREAD = 0 ;
	const STATE_READ = 1;
	
	public static function create( $initialValues, $class=self::TCLASS ){
		$object = parent::create($initialValues, $class);
		return $object;
	}


	public function save() {
		$tos = explode( ";", $this[self::F_ID_USER_TO] ) ;
		
		if( !isset( $this[self::F_ID_USER_FROM] ) ) {
			$user = Auth::getDefault()->getUser() ;
			$this[self::F_ID_USER_FROM] =  $user["id"] ;
			$to[] = $user["id"] ;
		}
		if( !isset( $this[self::F_STATE] ) ) {
			$this[self::F_STATE] = self::STATE_UNREAD ;
		}
		
		if( !isset( $this[self::F_DATE_CREATE] ) ) {
			$this[self::F_DATE_CREATE] = date( "Y-m-d H:i:s" );
		}
		
		$data = array();
		$data[self::F_TOPIC] = $this[self::F_TOPIC];
		$data[self::F_CONTENT] = $this[self::F_CONTENT];
		if( isset($this[self::F_ID_PARENT]) )
			$data[self::F_ID_PARENT] = $this[self::F_ID_PARENT];
		$data[self::F_ID_USER_FROM] = $this[self::F_ID_USER_FROM];
		$data[self::F_DATE_CREATE] = $this[self::F_DATE_CREATE];
		$data[self::F_STATE] = $this[self::F_STATE];
		//print_r($data);
		foreach( $tos as $to ) {
			if( $to != "" ) {
				$data[self::F_ID_USER_TO] = $to ;
				$cm = CommInternalMessage::create( $data ) ;
				//print_r($cm);
				//exit;
				$cm->simpleSave();
			}
		}
		
		//echo "<pre>" ;
		//print_r($this);
		//exit;
		
		
		
	}
	
	private function simpleSave() {
		parent::save();
	}
	
	
	// zwraca wiadomości dla danego użytkownika
	/**
	 * 
	 * @param bool|null $unreed = true - tylko nieprzeczytane, false - tylko przeczytane| null - wszystkie 
	 * @param unknown_type $id
	 */
	public static function getMessage( $unreed = null, $id = null ) {
		if( $id == null ) {
			$id = Auth::getDefault()->getUser()->getValue("id") ;		
		}
		
		$crit = new Criteria();
		$crit->addCondition( CommInternalMessage::F_ID_USER_TO, $id );
		$crit->addCondition( CommInternalMessage::F_ID_USER_FROM, $id, Criteria::C_NOT_EQUAL );
		
		if($unreed === true )
			$crit->addCondition( CommInternalMessage::F_STATE, self::STATE_UNREAD );
		else if($unreed === false )
			$crit->addCondition( CommInternalMessage::F_STATE, self::STATE_READ );
		
		/*echo "<pre>" ;
		print_r($crit);
		exit;
		*/	
		$cmms = CommInternalMessage::getByCriteria( $crit, CommInternalMessage::TCLASS ) ;
		return $cmms ;
		
	}

//*END OF USER AREA*//
}
?>