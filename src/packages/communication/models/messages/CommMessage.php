<?php





class CommMessage extends \Arrow\Package\Database\ProjectPersistent{

	const TCLASS = __CLASS__;
	const F_ID = "id";
	const F_DATE = "date";
	const F_SIZE = "size";
	const F_FLAGS = "flags";
	const F_TYPE = "type";
	const F_CONTACT_ID = "contact_id";
	const F_SUBJECT = "subject";
	const F_FROM = "from";
	const F_TO = "to";
	const F_MESSAGE_ID = "message_id";
	const F_PLAIN = "plain";
	const F_HTML = "html";


	//*USER AREA*//
	
	const EMAIL_PATERN = '/[A-Za-z0-9\._-]+@[A-Za-z0-9_-]+(\.[A-Za-z0-9_-]+)+/' ;

	// typ 
	const MESSAGE_IN = 1 ;
	const MESSAGE_OUT = 2 ;
	
	
	const F_SIZE_EXT = "size_ext";
	const F_PREVIEW = "preview";
	
	// message flags 
	const M_RECENT = 1;
	const M_FLAGGED = 2;
	const M_ANSWERED = 4;
	const M_DELETED = 8;
	const M_SEEN = 16;
	const M_DRAFT = 32;
	
	public static function create( $initialValues, $class=self::TCLASS ){
		$object = parent::create($initialValues, $class);
		return $object;
	}
	
	public function getValue($field) {
		
		switch($field) {
			case 'size_ext':
				$size = $this['size'];
				$tmp = array('B', 'KB', 'MB');
				$current = 0;
				while($size > 1024) {
					$current++;
					$size = $size/1024;
					if($current == 2) break;
				}
				$value = number_format($size, 2, ',', ' ').' '.$tmp[$current];
				break;
			case 'preview':
				
				if(!empty($this['html'])){
					$value = parent::getValue('html');
					$attachments = $this->getAttachments('html');
					
					foreach($attachments as $at)
						if(!empty($at['cid']))
							$value = preg_replace("/cid:{$at['cid']}/", $at['path'], $value);
				}
				else {
					$header = 	'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'.
								'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">'.
								'<head><meta http-equiv="Content-type" content="text/html; charset=utf-8" /></head><body>';

					$value = parent::getValue('plain');
					$value = $header.nl2br(htmlentities($value, ENT_QUOTES, 'UTF-8'))."</body></html>";

				}
				break;
			
			default:
				$value = parent::getValue($field);
				break;
		}
		
		return $value;
	}
	
	public function getAttachments($subset = '') {
		$criteria = new Criteria();
		$criteria->addCondition('message_id', $this['id']);
		
		if($subset == 'user')
			$criteria->addCondition('cid', '');
		elseif($subset == 'html')
			$criteria->addCondition('cid', '', Criteria::C_NOT_EQUAL);
		
		return CommMessageAttachments::getByCriteria($criteria, CommMessageAttachments::TCLASS);
	}
	
	public function getEmailsFrom() {
		$matches = array();
		preg_match_all( self::EMAIL_PATERN ,$this["from"],$matches);
		return $matches[0] ;
	}
	
	public function getEmailsTo() {
		$matches = array();		
	    preg_match_all( self::EMAIL_PATERN ,$this["to"],$matches) ;
		return $matches[0] ;
	}
	
	//*END OF USER AREA*//
}
?>