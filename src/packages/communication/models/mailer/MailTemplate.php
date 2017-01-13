<?php
namespace Arrow\Communication;

class MailTemplate extends \Arrow\ORM\ORM_Arrow_Package_Communication_MailTemplate{

	//*USER AREA*//
	const PATH = "/mail_templates/";

	public function getEmails() {
		$dats = \Arrow\Models\Settings::getDefault()->getSetting( $this ) ;
		if( !empty($datas) )
		return $dats["emails"]->getSetting() ;
		else return "" ;
	}

	public function getContent() {
		return $this["content"];
	}

	public function getLangFields(){
		return array(  "title" );
	}

	public function save(){
		$this["content"] = str_replace("&gt;", ">",$this["content"]);
		parent::save();

	}
	//*END OF USER AREA*//
}
?>