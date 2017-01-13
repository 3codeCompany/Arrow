<?php





class CommNewsletterCampaign extends \Arrow\Package\Database\ProjectPersistent implements IAction{

	const TCLASS = __CLASS__;
	const F_ID = "id";
	const F_STATUS = "status";
	const F_NAME = "name";
	const F_TEMPLATE_ID = "template_id";
	const F_TMP_ID = "tmp_id";
	const F_START_TIME = "start_time";
	const F_DESCRIPTION = "description";
	const F__STATUS = "_status";
	const F_ACTIVE = "active";


	//*USER AREA*//
	
	const STATUS_NEW = 0;
	const STATUS_TO_SEND = 1;
	const STATUS_SENDING = 2;
	const STATUS_STOPED = 3;
	const STATUS_FINISHED = 4;
	
	public static function create( $initialValues, $class=self::TCLASS ){
		$initialValues[self::F_ACTIVE] = 0;
		$object = parent::create($initialValues, $class);
		return $object;
	}
	
	public function setValue( $id, $value, $temp = false ){
		
		if( $id == "groups"){ 
			$this->addN2NConnection( 
				"communication.newsletter.CommNewsletterGroup", 
				"communication.newsletter.CommNewsletterCampaignGroup", 
				$value
			);
			return;
		}
		parent::setValue($id, $value, $temp);
	}	

	public function getGroups(){
		$criteria = new Criteria();
		$criteria->addCondition(CommNewsletterCampaignGroup::F_CAMPAIGN_ID, $this->getPKey());
		$conn  = CommNewsletterCampaignGroup::getKeyValuePair(CommNewsletterCampaignGroup::F_GROUP_ID, $criteria, CommNewsletterCampaignGroup::TCLASS);
		$criteria = new Criteria();
		$criteria->addCondition(CommNewsletterGroup::F_ID, $conn, Criteria::C_IN);
		$criteria->addCondition(CommNewsletterGroup::F_TMP_ID, 0);
		return CommNewsletterGroup::getByCriteria( $criteria, CommNewsletterGroup::TCLASS );
	}
	
	public function delete(){
			//remove CommNewsletterCampaignGroup
		$criteria = new Criteria();
		$criteria->addCondition(CommNewsletterCampaignGroup::F_CAMPAIGN_ID, $this->getKey());
		$connections = CommNewsletterCampaignGroup::getByCriteria($criteria, CommNewsletterCampaignGroup::TCLASS);	
		foreach ($connections as $connection)
			$connection->delete();

		$criteria = new Criteria();
		$criteria->addCondition(CommNewsletterQueue::F_CAMPAIGN_ID, $this->getPKey());
		$result = CommNewsletterQueue::getByCriteria($criteria, CommNewsletterQueue::TCLASS);
		foreach($result as $obj)
			$obj->delete();

		parent::delete();
	}
	
	public function doAction( $name, $data ){
		switch($name){
			case 'stop':
				$this[self::F_STATUS] = self::STATUS_STOPED;
				$this->save();
			break;
			case 'start':
				$this[self::F_STATUS] = self::STATUS_NEW;
				$this->save();
			break;
			case 'reset':
				//usu� wys�ane wiadomo�ci
				$criteria = new Criteria();
				$criteria->addCondition(CommNewsletterQueue::F_CAMPAIGN_ID, $this->getPKey());
				$result = CommNewsletterQueue::getByCriteria($criteria, CommNewsletterQueue::TCLASS);
				foreach($result as $obj) $obj->delete();
				$this[self::F_STATUS] = self::STATUS_NEW;
				
				$this->save();
			break;
		}
		
	}
	
	public static function doStaticAction( $name, $data ) { return false ; }

	//*END OF USER AREA*//
}
?>