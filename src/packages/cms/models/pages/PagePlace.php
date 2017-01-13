<?php
namespace Arrow\CMS;


class PagePlace extends \Arrow\ORM\ORM_Arrow_CMS_PagePlace{

    public function afterObjectCreate(\Arrow\ORM\PersistentObject $object)
    {
        $this->synchronizePages();
    }
	
	protected function synchronizePages(){
		$criteria = new Criteria();
		$criteria->setEmptyList();
		$criteria->addColumn(Page::F_ID);
		$pages = OrmPersistent::getByCriteria($criteria, Page::TCLASS);

		foreach($pages as $page){
			$conf = PagePlaceConf::create(
				array( 
					PagePlaceConf::F_PAGE_ID => $page[Page::F_ID],
					PagePlaceConf::F_PLACE_ID => $this[self::F_ID]
				) 
			);
			$conf->save();
		}	
	}

	public function delete(){
		$criteria = new Criteria();
		$criteria->addCondition( PagePlaceConf::F_PLACE_ID, $this[self::F_ID] );
		$conf = PagePlaceConf::getByCriteria($criteria, PagePlaceConf::TCLASS);
		foreach($conf as $c)
			$c->delete();
		parent::delete();
	}
	
	//*END OF USER AREA*//
}
?>