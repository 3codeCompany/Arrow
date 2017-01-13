<?php






class CommMailbox extends \Arrow\Package\Database\ProjectPersistentTreeNode implements IAction {

	const TCLASS = __CLASS__;
	const F_ID = "id";
	const F_LEFT_ID = "left_id";
	const F_RIGHT_ID = "right_id";
	const F_PARENT_ID = "parent_id";
	const F_DEPTH = "depth";
	const F_FLAGS = "flags";
	const F_ADDRESS_ID = "address_id";
	const F_IMAP_NAME = "imap_name";
	const F_NAME = "name";
	const F__STATE = "_state";


	//*USER AREA*//
	const F_MSGNO = "msgno";
	const F_SEEN_MSGNO = "seen_msgno";
	
	// mailbox flags 
	const M_VISIBLE = 1;
	const M_CONTAINER = 2;
	
	public static function create( $initialValues, $class=self::TCLASS ){
		$object = parent::create($initialValues, $class);
		return $object;
	}
	
	public function getValue($field) {
		
		switch($field) {
			
			case 'msgno':
				$criteria = new Criteria();
				$criteria->setEmptyList();
				$criteria->addColumn('id', 'count', Criteria::A_COUNT);
				$criteria->addCondition('mailbox_id', $this['id']);
				$criteria->addCondition('message_id', null, Criteria::C_NOT_EQUAL);
				$result = CommMailboxMessage::getByCriteria($criteria, CommMailboxMessage::TCLASS);
				
				if(!empty($result))
					$value = $result[0]['count'];
				else
					$value = 0;
				break;
				
			case 'seen_msgno':
				$critMboxMsg = new Criteria('communication.messages.CommMailboxMessage');
				$critMsg = new Criteria('communication.messages.CommMessage');
				
				$join = new OrmJoinCriteria( array($critMboxMsg, $critMsg ) );
				$join->setEmptyList();
				$join->addColumn('CommMailboxMessage:id', 'count', Criteria::A_COUNT);
				$join->addCondition('CommMailboxMessage:mailbox_id', $this['id']);
				$join->addCondition('CommMailboxMessage:message_id', null, Criteria::C_NOT_EQUAL);
				$join->addCondition('CommMessage:flags', CommMessage::M_SEEN, Criteria::C_BIT_AND);
				
				$result = CommMailboxMessage::getDataSetByCriteria($join, CommMailboxMessage::TCLASS);
				
				if(!empty($result))
					$value = $result[0]['CommMailboxMessage']['count'];
				else
					$value = 0;
				break;
				
			case 'unseen_msgno': 
				$value = $this['msgno'] - $this['seen_msgno'];
				break;
				
			default:
				$value = parent::getValue($field);
				break;
		}
		
		return $value;
	}
	
	public function doAction( $name, $data ) {
		
		switch( $name ) {
			case "reparse":

				// Pobranie wszystkich maili ze skrzynki które nie są jeszcze kontaktami
				$critmessage = new Criteria( "communication.messages.CommMessage" ) ;
				$critmessage->addCondition( "contact_id", "NULL" );
				$connector = new Criteria("communication.messages.CommMailboxMessage");
				$connector->addCondition( "mailbox_id", $this["id"] );
				
				$join = new OrmJoinCriteria( array( $critmessage, $connector ) );
				$ds = CommMessage::getByCriteria($join, CommMessage::TCLASS) ;
				
				//foreach
				//print_r( count($ds) );
				//exit;
				$cnt = 0 ; $all = count( $ds );
				foreach( $ds as $message ) {
					if( CommMessageParser::parse($message, null, $message->getAttachments() ) ) ++$cnt;
				}
				
				Interaction::info( "Przeparsowałem wiadomości <b>$all</b> <br /> Dodałem <b>$cnt</b> nowych wiadomości kontaktowych" , "Przeparsowałem skrzynkę", true );
				return true;
		}
		return false;
		
		
		return false ;
	}
	
	public static function doStaticAction( $name, $data ) { return false ; }
	
	//*END OF USER AREA*//
}
?>