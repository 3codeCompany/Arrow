<?php
namespace Arrow\CMS;

class CMSArticle extends \Arrow\ORM\ORM_Arrow_CMS_CMSNews{

	const TCLASS = __CLASS__;
	const F_ID = "id";
	const F_ACTIVE = "active";
	const F_ISSUE = "issue";
	const F_FREE = "free";
	const F_TITLE = "title";
	const F_CONTENT = "content";
	const F_DATE = "date";
	const F_TMP_ID = "tmp_id";
	const F_ISFREE = "isfree";
	const F_PAGE = "page";
	const F_SORT = "sort";
	const F_SUBTITLE = "subtitle";
	const F_CATEGORY_TAG_1 = "category_tag_1";
	const F_CATEGORY_TAG_2 = "category_tag_2";
	const F_CATEGORY_TAG_3 = "category_tag_3";
	const F_ON_MAIN = "on_main";
	const F_LIST_SHORT_TEXT = "list_short_text";


	//*USER AREA*//

	public static function create( $initialValues, $class=self::TCLASS ){
		$object = parent::create($initialValues, $class);
		$object->save();
		$object["sort"] = $object->getPKey();
		return $object;
	}
	
	public function getValue($field){
		
		if($field == "textAuthors" || $field == "photoAuthors"){
			$criteria = new Criteria( "app.AppAuthor" );
			$criteria1 = new Criteria( "app.AppAuthorConnection" );
			$criteria1->addCondition("connection_desc", $field == "photoAuthors" ? "photo": "text");
			$criteria1->addCondition("class", "CMSArticle");
			$criteria1->addCondition("object_id", $this->getPKey());
			return implode(";", AppAuthor::getKeyValuePair("id", new OrmJoinCriteria(array( $criteria, $criteria1 )), AppAuthor::TCLASS));
		}
		
		if($field == "textAuthorsFull" || $field == "photoAuthorsFull"){
			$criteria = new Criteria( "app.AppAuthor" );
			$criteria1 = new Criteria( "app.AppAuthorConnection" );
			$criteria1->addCondition("connection_desc", $field == "photoAuthorsFull" ? "photo": "text");
			$criteria1->addCondition("class", "CMSArticle");
			$criteria1->addCondition("object_id", $this->getPKey());
			return AppAuthor::getByCriteria( new OrmJoinCriteria(array( $criteria, $criteria1 )), AppAuthor::TCLASS);
		}		
		
	
		return parent::getValue($field);
	}
	
	
	public function setValue($field, $value, $tmp = false){
		
		if($field == "textAuthors" || $field == "photoAuthors"){
			return $this->addN2NConnection( 
				"app.AppAuthor", 
				"app.AppAuthorConnection", 
				$value,
				array( "class" => "CMSArticle", "connection_desc" => ($field == "photoAuthors") ? "photo": "text" ),
				"author_id", "object_id"
			);
		}
	
		return parent::setValue($field, $value, $tmp);
	}	
	
	
	public function getLink(){
	    
		return $this["subtitle"];
	    return "artykul,{$this["seo_rewrite"]},".$this->getPKey();
	    //return "artykul,".$this->getPKey();
	}
	
	public function generateTitleInfo(){
	    try{
		if($this["section"])
		    $section = Criteria::query(UtilsDictionary::getClass())->findByKey($this["section"]);
		if($this["region"])
		    $region = Criteria::query(UtilsDictionary::getClass())->findByKey($this["region"]);
		if(isset($section))
			$first = $section["name"];
		
		$tmp = "";
		if(isset($region)){
		    $anc = $region->getAncestors();
		    $tmp = "";
		    foreach($anc as $node){
			    if(!isset($first) && $node["depth"] == 2)
				    $first = $node["name"];
			    elseif(isset($first) and $node["depth"] > 2)
				    $tmp = " <span>{$node["name"]}</span> ";
		    }
		}
		return "<strong>{$first}</strong> {$tmp} <span>{$region["name"]}</span>";
	    }catch(Exception $ex){
		return "--";
	    }
	}
/*	
	 Kontynent | - pierwsza kreska zielona - nastepne zwykle Kraj | Region - jak nie ma dzialu dla artu 
11:22:22 
Dzial | pierwsza kreska zielona - Kraj | Region  */

	//*END OF USER AREA*//
}
?>