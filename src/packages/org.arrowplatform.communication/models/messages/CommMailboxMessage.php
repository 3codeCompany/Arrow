<?php



class CommMailboxMessage extends \Arrow\Package\Database\ProjectPersistent{

	const TCLASS = __CLASS__;
	const F_ID = "id";
	const F__STATE = "_state";
	const F_MAILBOX_ID = "mailbox_id";
	const F_MESSAGE_UID = "message_uid";
	const F_MESSAGE_ID = "message_id";
	const F_NEXT_SYNC = "next_sync";


	//*USER AREA*//
	public static function create( $initialValues, $class=self::TCLASS ){
		$object = parent::create($initialValues, $class);
		return $object;
	}
	//*END OF USER AREA*//
}
?>