<?php




class CommNewsletterCampaignGroup extends \Arrow\Package\Database\ProjectPersistent{

	const TCLASS = __CLASS__;
	const F_ID = "id";
	const F_CAMPAIGN_ID = "campaign_id";
	const F_GROUP_ID = "group_id";


//*USER AREA*//

public static function create( $initialValues, $class=self::TCLASS ){
	$object = parent::create($initialValues, $class);
	return $object;
}

//*END OF USER AREA*//
}
?>