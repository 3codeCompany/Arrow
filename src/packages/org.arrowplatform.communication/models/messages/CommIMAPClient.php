<?php


class CommIMAPClient extends \Arrow\Object{
	
	private $server = '';
	private $port = '';
	private $flags = '';
	private $user = '';
	private $password = '';
	private $mbox = array('imap_name' => '');
	private $mbox_res = false;
	private $mailboxes = array();
	
	public $error = null;
	
	function __construct($server, $port, $flags, $user, $password) {
		$this->server = $server;
		$this->port = $port;
		$this->flags = $flags;
		$this->user = $user;
		$this->password = $password;
		
		$this->mbox_res = @imap_open("{{$this->server}:{$this->port}{$this->flags}}", $this->user, $this->password);
    	
		//list mailboxes
		if($this->mbox_res !== false) {
			$tmp = imap_getmailboxes($this->mbox_res, "{{$this->server}{$this->flags}}", "*");
    		
			if (is_array($tmp))
				foreach ($tmp as $key => $val) {
					
					$imap_name = substr(strrchr($val->name, '}'), 1);
					$name = mb_convert_encoding( $imap_name, "UTF-8", "UTF7-IMAP" );
					
					$this->mailboxes[$key] = array(	'name' => $name,
													'imap_name' => $val->name,
													'delimiter' => $val->delimiter,
													'attr' => $val->attributes
					);
					
					$status = imap_status($this->mbox_res, $val->name, SA_ALL);
					if($status) {
						$this->mailboxes[$key]['msg_count'] = $status->messages;
						$this->mailboxes[$key]['unseen'] = $status->unseen;
					}
					else {
						$this->error = imap_last_error();
						$this->mailboxes[$key]['msg_count'] = -1;
						$this->mailboxes[$key]['unseen'] = -1;
					}
				}
		}
		else {
			$this->error = imap_last_error();
			throw new \Arrow\Exception(array("msg" =>"Błąd połączenia z serwerem IMAP ($user).<br/>Sprawdź, czy wszystkie parametry są poprawne.<br/>[{$this->error}]"));
		}
	}
	
	public function getMailboxList() {
		return $this->mailboxes;
	}
	
	private function checkConnection($force_reopen = false) {
		
		if($this->mbox_res !== false) {
			if(!imap_ping($this->mbox_res) || $force_reopen)
				return imap_reopen($this->mbox_res, $this->mbox['imap_name']);
		}
		else return false;
	}
  
	public function setCurrentMailbox($key) {
		
		$check = false;
		
		if(isset($this->mailboxes[$key]) && !($this->mailboxes[$key]['attr'] & LATT_NOSELECT)) {
			$this->mbox = $this->mailboxes[$key];
			$check = $this->checkConnection(true);
			
			if(!$check)
				$this->error = imap_last_error();
		}
		
		return $check;
	}
	
	public function getMids() {
		
		$mids = array();
		
		if(!empty($this->mbox) && !empty($this->mbox_res) && ($this->mbox['msg_count'] > 0)) {
			
			if($this->checkConnection() === false)
				return false;
			
			// sort options
			/*$sort = explode(':', $sort);
			if(isset($sort[1]) && $sort[1] == 'asc')
				$sort[1] = false;
			else
				$sort[1] = true;
			
			switch($sort[0]) {
				case 'subject': $sort[0] = SORTSUBJECT; break;
				case 'from': $sort[0] = SORTFROM; break;
				case 'size': $sort[0] = SORTSIZE; break;
				case 'date': $sort[0] = SORTDATE; break;
				case 'adate': $sort[0] = SORTARRIVAL; break;
				default: $sort = false; break;
			}
			
			//if($sort !== false)
				//$mids = imap_sort($this->mbox_res, $sort[0], $sort[1]);
			
			if(!empty($condition))
				$tmp = imap_search($this->mbox_res, $condition, SE_UID);
			else
				$tmp = imap_search($this->mbox_res, 'ALL', SE_UID);
			
			foreach($tmp as $uid)
				$mids[$uid] = imap_msgno($this->mbox_res, $uid);*/
				
			/*print_r($cmids);
			
			if(!empty($sort) && !empty($condition))
				$mids = array_slice(array_intersect($mids, $cmids), $offset, $length);
			else
				$mids = array_slice(array_merge($mids, $cmids), $offset, $length);
		
			print_r($mids);*/
			
			$tmp = imap_sort($this->mbox_res, SORTARRIVAL, true, SE_UID | SE_NOPREFETCH);
			foreach($tmp as $uid)
				$mids[$uid] = imap_msgno($this->mbox_res, $uid);
		}
		
		return $mids;
	}
	
	/* 
	 * Wygeneruj message_id dla wiadomości nie zawirających pola Message-Id
	 * 
	 */
	private function generateMessageId($mid) {
		
		// get header and body
		$hash = '';
		if(!empty($this->mbox) && !empty($this->mbox_res)) {
			
			//$body = imap_body($this->mbox_res, $mid, FT_PEEK);
			$header = imap_fetchheader($this->mbox_res,$mid);
			$hash = md5($header).'@internalhash';
		}
		
		return $hash;
	}
	
	private function decodeMIME($text) {
		
		$tmp = preg_replace('/(=[a-fA-F0-9]{2})/e', "strtoupper('\\1')", $text);
		
		if(($result = @iconv_mime_decode($tmp, 0, 'UTF-8')) === false) {
			
			// rozbij na kodowanie oraz tekst
			$result = imap_mime_header_decode($tmp);
			
			$str = '';
			foreach($result as $res) {
				
				// spróbuj wykryć kodowanie
				if(($enc = mb_detect_encoding($res->text)) === false)
					$enc = 'cp1250';
				
				$test = false;
				$test = @iconv($enc, 'UTF-8', $res->text);
				
				// sprawdź czy konwersja była poprawna
				if($test !== false)
					$res->text = $test;
				
				$str .= $res->text.' ';
			}
			
			$result = $str;
		}
		
		return $result;
	}
	
	public function getOverview($mid) {
		
		$default = array(
						'subject' =>'',
						'from' =>'',
						'to' =>'',
						'date' =>'',
						'message_id' =>'',
						'references' =>'',
						'in_reply_to' =>'',
						'size' =>'',
						'uid' =>'',
						'msgno' =>'',
						'recent' =>'',
						'flagged' =>'',
						'answered' =>'',
						'deleted' =>'',
						'seen' =>'',
						'draft' =>''
				);
		
		$overview = array();
		if(!empty($this->mbox) && !empty($this->mbox_res)) {
			
			$overview = imap_fetch_overview($this->mbox_res, $mid, 0);
			
			//MIME header decode
			if(isset($overview[0])) {
				
				$overview = get_object_vars($overview[0]);
				
				foreach($overview as $fk=>$field)
					$overview[$fk] = $this->decodeMIME($field);
			}
		}
		
		return array_merge($default, $overview);
	}
	
	public function getHeader($mid) {
		
		$default = array(
						'toaddress' =>'',
						'to' =>'',
			 			'fromaddress' =>'',
						'from' =>'',
						'ccaddress' =>'',
						'cc' =>'',
						'bccaddress' =>'',
						'bcc' =>'',
						'replay_toaddress' =>'',
						'replay_to' =>'',
						'senderaddress' =>'',
						'sender' =>'',
						'return_pathaddress' =>'',
						'return_path' =>'',
						'replay_toaddress' =>'',
						'remail' =>'',
						'date' =>'',
						'Date' =>'',
						'subject' =>'',
						'Subject' =>'',
						'in_reply_to' =>'',
						'message_id' =>'',
						'newsgroup' =>'',
						'followup_to' =>'',
						'references' =>'',
						'Recent' =>'',
						'Unseen' =>'',
						'Flagged' =>'',
						'Answered' =>'',
						'Deleted' =>'',
						'Draft' =>'',
						'Msgno' =>'',
						'MailDate' =>'',
						'Size' =>'',
						'udate' =>'',
						'fetchfrom' =>'',
						'fetchsubject' =>''
				);
		
		$header = array();
		
		if(!empty($this->mbox) && !empty($this->mbox_res)) {
			
			$header = imap_headerinfo($this->mbox_res,$mid);
			if(isset($header)) {
				
				$header = get_object_vars($header);
				
				foreach($header as $fname => $field) {
					if(is_array($field))
						foreach($field as $fkey => $item){
							$item = get_object_vars($item);
							foreach($item as $ikey => $f) 
								$item[$ikey] = $this->decodeMIME($f);
							
							$field[$fkey] = $item;
						}
					else $field = $this->decodeMIME($field);
					
					$header[$fname] = $field;
				}
			}
		}
		
		return array_merge($default, $header);
	}
	
	public function getMessage($mid) {
    	
		$message = false;
		if(!empty($this->mbox_res) && ($this->mbox['msg_count'] > 0)) {
			$message = array('html' => '', 'plain' => '', 'attachments' => array(), 'header' => array(), 'overview' => array());
			
			// HEADER
			$message['header'] = $this->getHeader($mid);
			
			// OVERVIEW
			$message['overview'] = $this->getOverview($mid);
			
			//check message-id field
			if(empty($message['header']['message_id']))
				$message['header']['message_id'] = $message['overview']['message_id'];
			
			if(empty($message['overview']['message_id']))
				$message['overview']['message_id'] = $message['header']['message_id'];
			
			if(empty($message['overview']['message_id']) && empty($message['header']['message_id']))
				$message['overview']['message_id'] = $message['header']['message_id'] = $this->generateMessageId($mid);
			
			// BODY
			$s = imap_fetchstructure($this->mbox_res,$mid);
			//echo '<pre>';
			//print_r($s);
			//exit;
			
			if(is_object($s)) {
				if (!isset($s->parts))  // not multipart
					$this->getBodyPart($mid,$s,0, $message);  // no part-number, so pass 0
				else {  // multipart: iterate through each part
					foreach ($s->parts as $partno0=>$p)
						$this->getBodyPart($mid,$p,$partno0+1, $message);
				}
			}
			else $message = 'Bad message number'; // TODO usunąć i coś wymyślić
		}
		
		return $message;
	}
  
  function getBodyPart($mid,$p,$partno, &$message) {
      // $partno = '1', '2', '2.1', '2.1.3', etc if multipart, 0 if not multipart
      //global $htmlmsg,$plainmsg,$charset,$attachments;
      
      // DECODE DATA
      $data = ($partno)?
          imap_fetchbody($this->mbox_res,$mid,$partno):  // multipart
          imap_body($this->mbox_res,$mid, FT_PEEK);  // not multipart
	
      //print_r("MID: $mid; PARTNO: $partno; TYPE: {$p->type}<br/>");
      //print_r('Data1: '.$data);
      //print_r('<br/>');
      ///print_r(imap_errors());
      
      // Any part may be encoded, even plain text messages, so check everything.
      //print_r('ENCO: '.$p->encoding);
      if ($p->encoding==4)
          $data = quoted_printable_decode($data);
      elseif ($p->encoding==3)
          $data = base64_decode($data);
      // no need to decode 7-bit, 8-bit, or binary
      
      // PARAMETERS
      // get all parameters, like charset, filenames of attachments, etc.
      $params = array();
      if ($p->ifparameters && $p->parameters)
          foreach ($p->parameters as $x)
              $params[ strtolower( $x->attribute ) ] = $x->value;
      if ($p->ifdparameters && $p->dparameters)
          foreach ($p->dparameters as $x)
              $params[ strtolower( $x->attribute ) ] = $x->value;

              
      // ATTACHMENT
      // Any part with a filename is an attachment,
      // so an attached text file (type 0) is not mistaken as the message.
      if (isset($params['filename']) || isset($params['name'])) {
      	
          // filename may be given as 'Filename' or 'Name' or both
          // filename may be encoded, so see imap_mime_header_decode()
          $filename = $this->decodeMIME((isset($params['filename']))? $params['filename'] : $params['name']);
          //print_r('Data2: '.$data);
      	  //print_r('<br/>');
          //print_r('Filename: '.$filename);
          //print_r('<br/><br/><br/>');
          if($p->ifid)
          	$id = trim($p->id, '<>');
          else
            $id = '';
          
          $message['attachments'][] = array('name' => $filename, 'data' => $data, 'id' => $id);
      }
      // TEXT
      elseif ($p->type==0 && $data) {
          // Messages may be split in different parts because of inline attachments,
          // so append parts together with blank row.
      	  if(isset($params['charset']) && !empty($params['charset']))
	      	$enc = $params['charset'];
	      else
	      	if(($enc = mb_detect_encoding($data)) === false)
		      	$enc = 'WINDOWS-1250';
      	  
      	  /*if(($enc = mb_detect_encoding($data)) === false) {
      	  	if(isset($params['charset']) && !empty($params['charset']))
	      		$enc = $params['charset'];
	      	else
		      	$enc = 'WINDOWS-1250';
      	  }*/
      	  
      	  if($enc == 'US-ASCII')
	      	$enc = 'WINDOWS-1250//TRANSLIT//IGNORE';
          
          $data = @iconv($enc, 'UTF-8', $data);
          
          $pattern = '/<meta(.*?)http-equiv=["]?Content-type["]?(.*?)>/is';
          $rep = '<meta http-equiv="Content-type" content="text/html; charset=utf-8" />';
          $data = preg_replace($pattern,$rep,$data);
          
          if (strtolower($p->subtype)=='plain')
              $message['plain'] .= $data."\n";
          else
              $message['html'] .= $data."<br/>";
      }

      // EMBEDDED MESSAGE
      // Many bounce notifications embed the original message as type 2,
      // but AOL uses type 1 (multipart), which is not handled here.
      // There are no PHP functions to parse embedded messages,
      // so this just appends the raw source to the main message.
	  elseif ($p->type == 2 && $data) { // Check to see if the part is an attached email message, as in the RFC-822 type
			
	  		//save embedded message as an attachment
	  		$message['attachments'][] = array('name' => "message_$partno.eml", 'data' => $data, 'id' => '');
	  		
      		/*if(!empty($params['charset'])) {
				$charset = $params['charset'];
				
				if($charset == 'US-ASCII')
					$charset = 'WINDOWS-1250//TRANSLIT//IGNORE';
			}
			
			$data = @iconv($charset, 'UTF-8', $data);
			$pattern = '/<meta(.*?)http-equiv="Content-type"(.*?)>/i';
          	$rep = '<meta http-equiv="Content-type" content="text/html; charset=utf-8" />';
          	$data = preg_replace($pattern,$rep,$data);
      		$message['plain'] = $data."\n";*/
			
	        //print_r($p);
	        /*if (sizeof($p->parts) > 0) {    // Check to see if the email has parts
	          foreach ($p->parts as $count => $part) {
	              // Iterate here again to compensate for the broken way that imap_fetchbody() handles attachments
	              if (sizeof($part->parts) > 0) {
	                  foreach ($part->parts as $count2 => $part2) {
	                  	  $this->getBodyPart($mid,$part2,$partno.'.'.($count2+1), $message);  // 1.2, 1.2.1, etc.
	                  }
	              }else{    // Attached email does not have a seperate mime attachment for text
	              	$message['plain'] = $data."<br><br>";
	              }
	          }
	          
	          return;
	      }*/
	    }
      
      // SUBPART RECURSION
      if (isset($p->parts)) {
          foreach ($p->parts as $partno0=>$p2)
              $this->getBodyPart($mid,$p2,$partno.'.'.($partno0+1), $message);  // 1.2, 1.2.1, etc.
      }
  }
}
?>