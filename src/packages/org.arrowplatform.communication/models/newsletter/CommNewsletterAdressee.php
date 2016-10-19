<?php




class CommNewsletterAdressee extends \Arrow\Package\Database\ProjectPersistent{

	const TCLASS = __CLASS__;
	const F_ID = "id";
	const F_EMAIL = "email";
	const F_STATUS = "status";
	const F_SINDIN = "sindin";
	const F_SINDOUT = "sindout";
	const F__STATE = "_state";


	//*USER AREA*//
	
	const STATUS_ACTIVE = 1;
	const STATUS_NO_ACTIVE = 0;

	public static function create( $initialValues, $class=self::TCLASS ){
		if (!isset($initialValues[self::F_SINDIN]) || empty($initialValues[self::F_SINDIN]))
			$initialValues[self::F_SINDIN] = date("Y-m-d H:i:s");
		if (!isset($initialValues[self::F_STATUS]))
			$initialValues[self::F_STATUS] = self::STATUS_ACTIVE;
		$object = parent::create($initialValues, $class);
		return $object;
	}
	
	public function setValue($field,$value, $tmp=false){
		if($field == self::F_STATUS){
			if($value == self::STATUS_ACTIVE)
				$this[self::F_SINDOUT] = null;
			else
				$this[self::F_SINDOUT] = date("Y-m-d H:i:s");
		}
		return parent::setValue($field,$value,$tmp);
	}

	//*END OF USER AREA*//
}
?>