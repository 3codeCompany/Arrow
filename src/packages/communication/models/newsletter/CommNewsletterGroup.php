<?php





class CommNewsletterGroup extends \Arrow\Package\Database\ProjectPersistent{

	const TCLASS = __CLASS__;
	const F_ID = "id";
	const F_NAME = "name";
	const F_DESCRIPTION = "description";
	const F__STATUS = "_status";
	const F_TMP_ID = "tmp_id";
	const F_TYPE = "type";
	
	//*USER AREA*//
	
	const FILTER_T_ALL = 0;
	const FILTER_T_FILTERS = 1;
	const FILTER_T_INDIVIDUAL = 2;
	const FILTER_T_INDIVIDUAL_AND_FILTERS = 3; // nieu�ywane tryb 1 pozwala na dodanie u�ykownik�w

	public static function create( $initialValues, $class=self::TCLASS ){
		if (isset($initialValues['filter-query-table']) )
			unset($initialValues['filter-query-table']);
		$object = parent::create($initialValues, $class);
		return $object;
	}
	
	public function setValue( $id, $value, $temp = false ){
		
		if ($id=='filter-query-table' )
			return;
		
		if( $id == "subscribers"){ 
			$this->addN2NConnection( 
				"communication.newsletter.CommNewsletterAdressee", 
				"communication.newsletter.CommNewsletterGroupSubscriber", 
				$value
			);
			return;
		}
		
		parent::setValue($id, $value, $temp);
	}
	public function getSubscribers(){
		//print_r($this['type']);
		if ($this['type'] == self::FILTER_T_INDIVIDUAL){
			$criteria = new Criteria();
			$criteria->addCondition(CommNewsletterGroupSubscriber::F_GROUP_ID, $this->getPKey());
			$conn  = CommNewsletterGroupSubscriber::getKeyValuePair(CommNewsletterGroupSubscriber::F_SUBSCRIBER_ID, $criteria, CommNewsletterGroupSubscriber::TCLASS);
			$criteria = new Criteria();
			$criteria->addCondition(CommNewsletterAdressee::F_ID, $conn, Criteria::C_IN);
			$subscribers = CommNewsletterAdressee::getByCriteria( $criteria, CommNewsletterAdressee::TCLASS );
			return $subscribers;
		}
		elseif ($this['type'] == self::FILTER_T_ALL){
			$criteria = new Criteria();
			$criteria->addCondition(CommNewsletterAdressee::F_ACTIVE, CommNewsletterAdressee::A_ACTIVE);
			$subscribers = CommNewsletterAdressee::getByCriteria( $criteria, CommNewsletterAdressee::TCLASS );
			return $subscribers;
		}
		elseif ($this['type'] == self::FILTER_T_FILTERS){
			//osoby wpisane r�cznie
			$group_criteria = new Criteria();
			$group_criteria->addCondition(CommNewsletterGroupSubscriber::F_GROUP_ID, $this->getPKey());
			$conn  = CommNewsletterGroupSubscriber::getKeyValuePair(CommNewsletterGroupSubscriber::F_SUBSCRIBER_ID, $group_criteria, CommNewsletterGroupSubscriber::TCLASS);
			$criteria = new Criteria();
			if (!empty($conn))
				$criteria->addCondition(CommNewsletterAdressee::F_ID, $conn, Criteria::C_IN);
			
			//osoby wpisane za pomoc� filtr�w
			$filter_object_criteria = new Criteria('utils.filters.UtilsFilterObject');
			$filter_object_criteria->addCondition('class', 'CommNewsletterGroup');
			$filter_object_criteria->addCondition('object_id', $this['id']);
			
			$filters_id = UtilsFilterObject::getByCriteria($filter_object_criteria, UtilsFilterObject::TCLASS);
			$filter_ids = array();
			foreach ($filters_id as $fid){
				$filter_ids[] = $fid[UtilsFilterObject::F_FILTER_ID];
			}
			$filter_criteria = new Criteria('utils.filters.UtilsFilter');
			$filter_criteria->addCondition('id', $filter_ids,Criteria::C_IN);
			$filter_data = UtilsFilter::getByCriteria($filter_criteria, UtilsFilter::TCLASS);
			//Filtry s� mi�dzy sob� po��czone przez OR
			$criteria->startGroup(Criteria::C_OR_GROUP);
			foreach ($filter_data as $filter){
				//warunki w pojedy�czym filtrze u�ywaj� AND
				$criteria->startGroup(Criteria::C_AND_GROUP);
				$criteria->fromString($filter['criteria']);
				$criteria->endGroup();
			}
			$criteria->endGroup();
			$subscribers = CommNewsletterAdressee::getByCriteria( $criteria, CommNewsletterAdressee::TCLASS );
			//FB::log(print_r(OrmMysql::getLastQuery()));
			return $subscribers;
		}
		
		return array();
	}

	public function countSubscribers(){
		//print_r($this['type']);
		if ($this['type'] == self::FILTER_T_INDIVIDUAL){
			$criteria = new Criteria();
			$criteria->addCondition(CommNewsletterGroupSubscriber::F_GROUP_ID, $this->getPKey());
			$criteria->addColumn('id','cnt', Criteria::A_COUNT);
			$cnt = CommNewsletterGroupSubscriber::getByCriteria($criteria, CommNewsletterGroupSubscriber::TCLASS);
			if (isset($cnt[0])) 
				return  $cnt[0]['cnt'];
			else			
				return 0;
		}
		elseif ($this['type'] == self::FILTER_T_ALL){
			$criteria = new Criteria();
			$criteria->addCondition(CommNewsletterAdressee::F_ACTIVE, CommNewsletterAdressee::A_ACTIVE);
			$criteria->addColumn('id','cnt', Criteria::A_COUNT);
			$cnt = CommNewsletterAdressee::getByCriteria( $criteria, CommNewsletterAdressee::TCLASS );
			if (isset($cnt[0])) 
				return  $cnt[0]['cnt'];
			else
				return 0;
		}
		elseif ($this['type'] == self::FILTER_T_FILTERS){
			//osoby wpisane r�cznie
			$group_criteria = new Criteria();
			$group_criteria->addCondition(CommNewsletterGroupSubscriber::F_GROUP_ID, $this->getPKey());
			$conn  = CommNewsletterGroupSubscriber::getKeyValuePair(CommNewsletterGroupSubscriber::F_SUBSCRIBER_ID, $group_criteria, CommNewsletterGroupSubscriber::TCLASS);
			$criteria = new Criteria();
			if (!empty($conn))
				$criteria->addCondition(CommNewsletterAdressee::F_ID, $conn, Criteria::C_IN);
			
			//osoby wpisane za pomoc� filtr�w
			$filter_object_criteria = new Criteria('utils.filters.UtilsFilterObject');
			$filter_object_criteria->addCondition('class', 'CommNewsletterGroup');
			$filter_object_criteria->addCondition('object_id', $this['id']);
			
			$filters_id = UtilsFilterObject::getByCriteria($filter_object_criteria, UtilsFilterObject::TCLASS);
			$filter_ids = array();
			foreach ($filters_id as $fid){
				$filter_ids[] = $fid[UtilsFilterObject::F_FILTER_ID];
			}
			$filter_criteria = new Criteria('utils.filters.UtilsFilter');
			$filter_criteria->addCondition('id', $filter_ids,Criteria::C_IN);
			$filter_data = UtilsFilter::getByCriteria($filter_criteria, UtilsFilter::TCLASS);
			//Filtry s� mi�dzy sob� po��czone przez OR
			$criteria->startGroup(Criteria::C_OR_GROUP);
			foreach ($filter_data as $filter){
				//warunki w pojedy�czym filtrze u�ywaj� AND
				$criteria->startGroup(Criteria::C_AND_GROUP);
				$criteria->fromString($filter['criteria']);
				$criteria->endGroup();
			}
			$criteria->endGroup();
			$criteria->addColumn('id','cnt', Criteria::A_COUNT);
			$cnt = CommNewsletterAdressee::getByCriteria( $criteria, CommNewsletterAdressee::TCLASS );
			if (isset($cnt[0])) 
				return  $cnt[0]['cnt'];
			else
				return 0;
		}
		
		return 0;
	}
	//*END OF USER AREA*//
}
?>