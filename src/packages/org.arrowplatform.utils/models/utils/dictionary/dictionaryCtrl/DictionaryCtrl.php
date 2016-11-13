<?php
namespace Arrow\Package\Utils;

use \Arrow\ORM\Persistent\Criteria,\Arrow\ORM\SqlRouter,\Arrow\Package\Utils\UtilsDictionary;
class DictionaryCtrl extends \Arrow\Controls\FormField{

	/*
	 * mode can be "values" or "referendces"
	 *   references - value of select is id of dictionary
	 *   values - value od select is id value of dictionary element 
	 * 
	 * name - the name of select in control
	 * type - 
	 * 
	*/
	
	public function init() {
		$this->registerJs( get_class($this), __DIR__."/DictionaryCtrl.js", "#".self::CTRL_NAMESPACE."-dictionary-".$this->getId(), array( "id" => $this->getId() ) );
		
		parent::init();
	}
	
	public function configure(){
		parent::configure();
		
		$this->addProperties(array(
			"mode" => "references",
			"name" => null,
			"type" => null,
			"state" => "select",
			"title" => "",
			"title2" => "",
			"empty" => "true",
			"mechanism" => "simple",
			"class" => "",
			"width" => 0,
			"addable" => 1
		));
		
		$this->addRequiredProperties(array("name", "type"));
		$this->addStateProperties(array("state"));
	
	}
	
	
	public function generateOutput(){
		$type = $this->getProperty("type") ;
		//if( $this->getProperty("state") == "select" ) {
			
			$name = $this->getProperty("name") ;
			$mode = $this->getProperty("mode") ;
			$width = (int) $this->getProperty("width") ;
			
			$obs = array();
            $cat = Criteria::query(UtilsDictionary::getClass())->c(UtilsDictionary::F_SYSTEM_NAME, $type)->find();
			if( !isset($cat[0]) ){
				$initVal = array(  UtilsDictionary::F_PARENT_ID => 1, UtilsDictionary::F_NAME => strtoupper( $type ), UtilsDictionary::F_SYSTEM_NAME => $type );
				if( $this->getProperty("mechanism") == "extend" ) 
					$initVal[UtilsDictionary::F_VALUE] = "value";
				$cat =  new UtilsDictionary( $initVal );
				$cat->save();
			} else $cat = $cat[0] ;
			
			/*$crit = new Criteria();
			$crit->addCondition(UtilsDictionary::F_PARENT_ID, $cat->getPKey());
			ProjectPersistent::getByCriteria($crit,UtilsDictionary::TCLASS );*/

			$q = "select id,name,value from utils_dictionary where parent_id=".$cat->getPKey();
            $db = \Arrow\Models\Project::getInstance()->getDB();
			$result = $db->query($q);
			$content = array();
			$empty = $this->getProperty("empty");
			if( $this->getProperty("empty") != "0" ){
				if($empty != "true" && $empty != "1" )
					$content["NULL"] = $empty;
				else
					$content["NULL"] = "-- brak --";
			}
			
			foreach($cat->getChildren() as $row){
				if( $mode == "values" )
					$content[$row["value"]] = $row->getPath("name"," | ", 2)." | ". $row["name"];
				else
					$content[$row["id"]] = $row->getPath("name"," | ", 2 ).(($row["depth"]>2)?" | ":""). $row["name"]; 
			
			}
			
			
			
			
			/*
			while($row = mysql_fetch_assoc($result)){
				if( $mode == "values" )
					$content[$row["value"]] = $row["name"];
				else
					$content[$row["id"]] = $row["name"]; 
			
			}*/ 
				
			
			$select = $this->addNode("form", "select", array("name" => $name, "content" => $content, "namespace" => $this->getProperty("namespace"), "class" => $this->getProperty("class") ));
			$select->addExternalProperties( $this->getExternalProperties() ) ;

			$title = $this->getProperty("title") ;
			
			$ret = "<div class=\"ctrl-dictionary\" id=\"".self::CTRL_NAMESPACE."-dictionary-{$this->getId()}\" >" ;
			$ret.= "{$title} ".$select->generate();

        /*
			if( AccessManager::getDefault()->check( "utils.dictionary.UtilsDictionary", "create", 1 ) && $this->getProperty("addable") ) {
				$link = $this->getStateChangeLink( array( "state" => "edit" ) ) ;
				$ret .= ' <a  href="'.$link.'" class="ctrl-action ctrl-action-create" >&nbsp;</a>';
			}

        */
			$ret .= "</div>" ;
		//	return $ret;
		
		//} else {
		if( $this->getProperty("state") == "edit" ) {
			$cancelLink = $this->getStateChangeLink( array( "state" => "select" ) ) ;
			
			$title = $this->getProperty("title") ;
			$title2 = $this->getProperty("title2") ;
			
			$crit = new Criteria() ;
			$crit->addCondition( UtilsDictionary::F_SYSTEM_NAME, $type );
			$cat = UtilsDictionary::getByCriteria( $crit, UtilsDictionary::TCLASS );
			$link = TemplateLinker::getDefault()->generateBeanLink( array( "path" => "/controller::actionRouter", "data[parent_id]" => $cat[0]["id"], "model" => "utils.dictionary.UtilsDictionary" ), true )  ;
			
			$ret .= "<div class=\"ctrl-popup\" id=\"".self::CTRL_NAMESPACE."-dictionary-{$this->getId()}\" >" ;
			$ret .= "{$title} <input class=\"dictionary-input\" type=\"text\" name=\"name\" >" ;
			if( $cat[0][ UtilsDictionary::F_VALUE ] == UtilsDictionary::MODE_VALUE ) {
				$ret .= " {$title2} <input class=\"dictionary-input-val\" type=\"text\" name=\"value\" >" ;
			}
			
			$ret .= ' &nbsp; <a class="ctrl-action ctrl-action-create ctrl-dictionary-_add" href="'.$link.'"  >Dodaj</a>';
			$ret .= '<a class="ctrl-action ctrl-action-cancel"  href="'.$cancelLink.'">Anuluj</a>';
			
			$ret .= "</div>" ;
		  //return $ret ;
		} 
		
		return $ret ;
	}


    public static  function validate( $proposedValue, $validationData ){
        return true;
    }
    
	

}
?>