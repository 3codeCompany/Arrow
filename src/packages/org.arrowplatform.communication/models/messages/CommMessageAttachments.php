<?php



class CommMessageAttachments extends \Arrow\Package\Database\ProjectPersistent{

	const TCLASS = __CLASS__;
	const F_ID = "id";
	const F_MESSAGE_ID = "message_id";
	const F_CID = "cid";
	const F_FILENAME = "filename";
	const F_PATH = "path";


	//*USER AREA*//
	
	public static function create( $initialValues, $class=self::TCLASS ){
		$object = parent::create($initialValues, $class);
		return $object;
	}
	
	public function getFilePath() {
		return $this['path'];
	}
	
	//*END OF USER AREA*//
}
?>