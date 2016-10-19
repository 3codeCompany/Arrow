<?php





class CommNewsletterQueue extends \Arrow\Package\Database\ProjectPersistent {

	const TCLASS = __CLASS__;
	const F_ID = "id";
	const F_STATUS = "status";
	const F_CAMPAIGN_ID = "campaign_id";
	const F_ADDED = "added";
	const F_SENDED = "sended";
	const F_SUBSCRIBER_ID = "subscriber_id";


	//*USER AREA*//
	
	const STATUS_NEW = 0;
	const STATUS_SENDED_OK = 1;
	const STATUS_SENDED_ERROR = 2;

	public static function create( $initialValues, $class=self::TCLASS ){
		$object = parent::create($initialValues, $class);
		return $object;
	}
	

	
	
	public static function proccess(){
		self::addToQueue();
		self::runQueue();
		self::finishActions();
	}
	
	public static function clearQueue(){
		$criteria = new Criteria();
		$cleartime = time() - (2 * 60 * 60);
		$criteria->startGroup('OR');
        	$criteria->startGroup('AND');
				$criteria->addCondition(self::F_SENDED, date("Y-m-d",$cleartime),Criteria::C_LESS_THAN);
				$criteria->addCondition(self::F_STATUS, 1);
			$criteria->endGroup();
			$cleartime = time() - (36 * 60 * 60);
			$criteria->startGroup('AND');
				$criteria->addCondition(self::F_SENDED, date("Y-m-d",$cleartime),Criteria::C_LESS_THAN);
				$criteria->addCondition(self::F_STATUS, 2);
			$criteria->endGroup();
		$criteria->endGroup();	
		$result = CommNewsletterQueue::getByCriteria($criteria, CommNewsletterQueue::TCLASS);
		foreach($result as $obj) $obj->delete();		
	}
	
	public static function addToQueue(){
		$criteria = new Criteria();
		$criteria->addCondition( CommNewsletterCampaign::F_STATUS, CommNewsletterCampaign::STATUS_NEW );
		$criteria->addCondition( CommNewsletterCampaign::F_START_TIME, date("Y-m-d H:i:s"), Criteria::C_LESS_EQUAL );
		$campaigns = CommNewsletterCampaign::getByCriteria( $criteria, CommNewsletterCampaign::TCLASS );
		foreach($campaigns as $campaign){
			foreach( $campaign->getGroups() as $group){
				foreach($group->getSubscribers() as $subscriber ){
					$criteria = new Criteria();
					$criteria->addCondition(self::F_CAMPAIGN_ID, $campaign->getPKey());
					$criteria->addCondition(self::F_SUBSCRIBER_ID, $subscriber->getPKey());
					$res = self::getByCriteria($criteria, self::TCLASS);
					if( empty($res) ){
						$data = array(
							self::F_ADDED => date("Y-m-d H:i:s"),
							self::F_CAMPAIGN_ID => $campaign->getPKey(),
							self::F_STATUS => self::STATUS_NEW,
							self::F_SUBSCRIBER_ID => $subscriber->getPKey(),
						);
						self::create($data)->save();
					}
				}
			}
			
			$campaign[CommNewsletterCampaign::F_STATUS] = CommNewsletterCampaign::STATUS_TO_SEND;
			$campaign->save();
		}
		
	}
	
	public static function runQueue(){
		$queueCrit = new Criteria("communication.newsletter.CommNewsletterQueue");
		//ilo�� pojedy�czych maili
		$queueCrit->setLimit(0,400);		
		$queueCrit->addCondition( self::F_STATUS, self::STATUS_NEW );
		
		$campaignCrit = new Criteria("communication.newsletter.CommNewsletterCampaign");
		$campaignCrit->addCondition( CommNewsletterCampaign::F_STATUS, CommNewsletterCampaign::STATUS_TO_SEND );
		$campaignCrit->addCondition( CommNewsletterCampaign::F_START_TIME, date("Y-m-d H:i:s"), Criteria::C_LESS_EQUAL );
		
		$templateCrit = new Criteria("communication.mailer.MailTemplate");
		
		$subscriberCrit = new Criteria("communication.newsletter.CommNewsletterAdressee");
		$subscriberCrit->addCondition(CommNewsletterAdressee::F_STATUS, CommNewsletterAdressee::STATUS_ACTIVE);
		
		$result = self::getByCriteria( new OrmJoinCriteria(array($queueCrit,$campaignCrit,  $templateCrit, $subscriberCrit)), self::TCLASS );
		
		foreach($result as $sendAction){
			
			$tmp = $sendAction->getRel(CommNewsletterCampaign::TCLASS. ":". MailTemplate::TCLASS);
			$template = array_shift($tmp);
			$tmp = $sendAction->getRel(CommNewsletterAdressee::TCLASS);
			$subscriber = array_shift($tmp);
			$result = CommMailerAPI::sendTemplate(
				$template, 
				$subscriber[CommNewsletterAdressee::F_EMAIL],
				array(), //data
				false
			); 
			
			if( $result ){
				$sendAction[self::F_SENDED] = date("Y-m-d H:i:s");
				$sendAction[self::F_STATUS] = self::STATUS_SENDED_OK;
			}else{
				$sendAction[self::F_STATUS] = self::STATUS_SENDED_ERROR;
			}
			
			$sendAction->save();
		}
		
	}
	
	public static function finishActions(){

		$campaignCrit = new Criteria();
		$campaignCrit->addCondition( CommNewsletterCampaign::F_STATUS, CommNewsletterCampaign::STATUS_TO_SEND );
		$activeCampaigns = CommNewsletterCampaign::getByCriteria($campaignCrit, CommNewsletterCampaign::TCLASS);
		
		foreach( $activeCampaigns as $aC ){
			
			$queueCrit = new Criteria();
			$queueCrit->setEmptyList();
			$queueCrit->addColumn( 'id', 'cnt', Criteria::A_COUNT );
			$queueCrit->addCondition( self::F_STATUS, self::STATUS_NEW );
			$result = self::getByCriteria($queueCrit, self::TCLASS);
						
			if(  $result[0]["cnt"] == 0 ){
				$aC[CommNewsletterCampaign::F_STATUS] = CommNewsletterCampaign::STATUS_FINISHED;
				$aC->save();
			}
		}
		
		
		
	}

	//*END OF USER AREA*//
}
?>