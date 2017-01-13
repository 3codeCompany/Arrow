<?php








class CommMessageManager extends \Arrow\Object{
	
	private static $instance = null;
	
	private $addresses = array();
	
	public static function create($addresses) {
		if(empty(self::$instance))
			self::$instance = new CommMessageManager($addresses);
		
		return self::$instance;
	}
	
	private function __construct($addresses = array()) {
		// skrzynki testowe
		$this->addresses = array_merge($this->addresses, $addresses);
		
		// TODO pobrać adresy zdefiniowane dla obecnego użytkownika + filtr skrzynek
		foreach($this->addresses as $key => $address) {
			
			$this->addresses[$key]['state'] = 'online';
			$error = true;
			
			// create address root node
			$criteria = new Criteria();
			$criteria->addCondition('address_id', $address['id']);
			$criteria->addCondition('imap_name', '');
			$res = CommMailbox::getByCriteria($criteria, CommMailbox::TCLASS);
			
			if(!empty($res))
				$address_root = $res[0];
			else {
				$address_root = CommMailbox::create(array('address_id' => $address['id'], 'imap_name' => '', 'name' => $address['email'], 'parent_id' => 1, '_state' => 0));
				$address_root->save();
			}
			
			if($address['type'] == 'imap') {
				
				$client = new CommIMAPClient($address['server'], $address['port'], $address['flags'], $address['user'], $address['password']);
				if($client) {
					$this->addresses[$key]['client'] = $client;
					
					// TODO dodać filtrowanie skrzynek z konfiguracji
					$mailboxes = $client->getMailboxList();
					
					// create mailboxes in DB
					foreach($mailboxes as $mkey => $mbox){
						
						$criteria = new Criteria();
						$criteria->addCondition('address_id', $address['id']);
						$criteria->addCondition('imap_name', $mbox['name']);
						$res =  CommMailbox::getByCriteria($criteria, CommMailbox::TCLASS);
						
						if(empty($res)) {
							
							$data = array();
							$data['parent_id'] = $address_root['id'];
							$data['flags'] = CommMailbox::M_VISIBLE;
							$data['address_id'] = $address['id'];
							$data['imap_name'] = $mbox['name'];
							$data['_state'] = 0;
							
							if($mbox['attr'] & LATT_NOSELECT)
								$data['flags'] |= CommMailbox::M_CONTAINER;
							
							//get parent id
							$tmp = explode($mbox['delimiter'], $mbox['name']);
							$length = count($tmp);
							$data['name'] = $tmp[$length-1];
							
							if($length > 1) {
								unset($tmp[$length-1]);
								$parent = implode($mbox['delimiter'], $tmp);
								
								// search for parent id in DB
								$criteria = new Criteria();
								$criteria->addCondition('address_id', $address['id']);
								$criteria->addCondition('imap_name', $parent);
								$par_res =  CommMailbox::getByCriteria($criteria, CommMailbox::TCLASS);
								
								if(!empty($par_res))
									$data['parent_id'] = $par_res[0]['id'];
							}
							
							$mailbox = CommMailbox::create($data);
						}
						else $mailbox = $res[0];
						
						$mailbox['_state'] = BitHelper::remove($mailbox['_state'], ProjectPersistent::STATE_DELETED);
						$mailbox->save();
						
						$this->addresses[$key]['mailboxes'][] = $mailbox;
					}
					
					$error = false;
				}
			}
			
			if($error)
				$this->addresses[$key]['state'] = 'offline';
		}
	}
	
	public function endSynchronize() {
		
		if(strpos(ob_get_contents(), 'Maximum execution time')) {
			ob_clean();
			$resp = JSONRemoteResponseHandler::getDefault();
			$resp->setHeaders();
			exit($resp->getResponse(array( "resp" => true)));
		}
	}
	
	public function synchronize($msg_count = 10, $address = null) {
		
		session_write_close();
		register_shutdown_function(array(&$this, "endSynchronize"));
		
		if($address === null)
			$addresses = $this->addresses; //synchronize all valid addresses
		elseif(isset($this->addresses[$address]))
			$addresses = array($address => $this->addresses[$address]); //synchronize only given address
		else
			return false; // invalid address
		
		foreach($addresses as $adr) {
			
			// iterate through mailboxes and download messages
			$count = 0;
			$client = $adr['client'];
			foreach($adr['mailboxes'] as $mbox_key => $mbox) {
				
				//check attributes first
				if($mbox['flags'] & CommMailbox::M_CONTAINER)
					continue;
				
				$client->setCurrentMailbox($mbox_key);
				$mids = $client->getMids();
				
				if($mids === false)
					continue;
				
				// get message ids
				$criteria = new Criteria();
				$criteria->setEmptyList();
				$criteria->addColumn('_state');
				$criteria->addColumn('message_uid');
				$criteria->addCondition('mailbox_id', $mbox['id']);
				$criteria->startGroup(Criteria::C_OR_GROUP);
					$criteria->addCondition('message_id', null, Criteria::C_NOT_EQUAL);
					$criteria->addCondition('next_sync', date('Y-m-d H:i:s'), Criteria::C_GREATER_THAN);
				$criteria->endGroup();
				$result = CommMailboxMessage::getByCriteria($criteria, CommMailboxMessage::TCLASS);
				
				// remove message ids that have been previously downloaded or should not be sync now
				// and delete messages from DB if removed from mailbox
				foreach($result as $item){
					if(isset($mids[$item['message_uid']]))
						unset($mids[$item['message_uid']]);
					else
						$item->delete();
				}
				
				//$mids = array_slice($mids, 0, $msg_count, true);
				
				// _add messages to local DB
				// uid - unique message id in selected mailbox
				// mid - message number in selected mailbox
				foreach($mids as $uid => $mid) {
					
					// link message with mailbox
					$link_data = array();
					$link_data['_state'] = 0;
					$link_data['mailbox_id'] = $mbox['id'];
					$link_data['message_uid'] = $uid;
					$link_data['message_id'] = null;
					
					$criteria = new Criteria();
					$criteria->addCondition('mailbox_id', $mbox['id']);
					$criteria->addCondition('message_uid', $uid);
					$res = CommMailboxMessage::getByCriteria($criteria, CommMailboxMessage::TCLASS);
					
					if(empty($res))
						$cMboxMsg = CommMailboxMessage::create($link_data);
					else
						$cMboxMsg = $res[0];
					
					$cMboxMsg['next_sync'] = date('Y-m-d H:i:s', strtotime('+1 hour'));
					$cMboxMsg->save();
					SqlRouter::query('COMMIT');
					SqlRouter::query('BEGIN');
					
					// get message
					$message = $client->getMessage($mid);
					$overview = $message['overview'];
					
					$data = array();
					$data['message_id'] = $message["header"]["message_id"] ; // content of Message-ID field!!
					$data['size'] = $overview['size'];
					$data['subject'] = htmlspecialchars($overview['subject']);
					$data['from'] = htmlspecialchars($overview['from']);
					
					if(!empty($overview['date']))
						$data['date'] = date('Y-m-d H:i:s', strtotime($overview['date']));
					else
						$data['date'] = date('Y-m-d H:i:s', $message['header']['udate']);
					
					if(!empty($message["header"]["to"]))
						$data['to'] = htmlspecialchars($this->makeFlatTo( $message["header"]["to"] ));
					else
						$data['to'] = '';
						
					if( strpos( $data['from'],$adr["email"] ) !== false )
						$data["type"] = CommMessage::MESSAGE_OUT ; 
					else if( strpos( $data['to'],$adr["email"] ) !== false )
						$data["type"] = CommMessage::MESSAGE_IN ;
					else $data["type"] = CommMessage::MESSAGE_IN ;		// może tak być w przypadku list mailingowych do których jesteś przypisany
					
					// flags
					$flags = 0;
					foreach($overview as $ov_key => $param) {
						if($param)
							switch($ov_key) {
								case 'recent': $flags |= CommMessage::M_RECENT;
								case 'flagged': $flags |= CommMessage::M_FLAGGED;
								case 'answered': $flags |= CommMessage::M_ANSWERED;
								case 'deleted': $flags |= CommMessage::M_DELETED;
								case 'seen': $flags |= CommMessage::M_SEEN;
								case 'draft': $flags |= CommMessage::M_DRAFT;
							}
					}
					$data['flags'] = $flags;
					
					if(!empty($message['html']))
						$data['html'] = $message['html'];
						
					if(!empty($message['plain']))
						$data['plain'] = $message['plain'];

					$criteria = new Criteria();
					$criteria->addCondition('message_id', $data['message_id']);
					$res = Persistent::getByCriteria($criteria, CommMessage::TCLASS);
					
					$parse = false;
					if(empty($res)) {
						$cmsg = CommMessage::create($data);
						$cmsg->save();
						$parse = true;
					}
					else
						$cmsg = $res[0];
					
					// save attachemants
					$attachments = array() ;
					foreach($message['attachments'] as $at_key => $at) {
						
						if(empty($at['name']))
							$at['name'] = 'noname.dat';
						
						$data = array('message_id' => $cmsg->getKey(), 'filename' => $at['name'], 'path' => '', 'cid' => $at['id']);
						$reserved = preg_quote('\/:*?"<>', '/');
						$path = "./cache/attachments/".$cmsg->getKey()."/{$cmsg->getKey()}-{$at_key}-".preg_replace("/[$reserved\s]/", "_",$at['name']);
						
						// create folder if neccessary
						if(!file_exists("./cache/attachments/".$cmsg->getKey()))
							mkdir("./cache/attachments/".$cmsg->getKey(), 0775);
						
						// save file
						$tmp = file_put_contents($path, $at['data']);
						if($tmp !== false) $data['path'] = $path;
						
						// save link in DB
						$criteria = new Criteria();
						$criteria->addCondition('path', $path);
						$res = CommMessageAttachments::getByCriteria($criteria, CommMessageAttachments::TCLASS);
						
						if(empty($res)) {
							$obj = CommMessageAttachments::create($data);
							$obj->save();
						}
						else $obj = $res[0];
						
						$attachments[] = $obj ;
					}
					
					$cMboxMsg['_state'] = 0;
					$cMboxMsg['message_id'] = $cmsg['id']; // CommMessage object id!!!
					$cMboxMsg['next_sync'] = null;
					$cMboxMsg->save();
					
					// parse message
					if( $parse === true )
						CommMessageParser::parse( $cmsg, $adr["email"], $attachments );
					
					// count downloaded messages
					$count++;
					if($count >= $msg_count){
						return true;
					}
				}
			}
		}
		
		return false;
	}
	
	private function makeFlatTo( $message_to ) {
		$ret = array() ;
		
		foreach( $message_to as $to ) {
			$str = "" ;
			if( isset($to["personal"]) )
				$str = "{$to["personal"]} " ;

			$str .= "<" ;
			if( isset($to["mailbox"]) )
				$str .= "{$to["mailbox"]}";
				
			if( isset($to["host"]) )	
				$str .= "@{$to["host"]}" ;
			
			$ret[] = $str.">" ;
		}
		return implode( ",", $ret ) ;
	}
	
	
	/*public function getMessages($addressId, $mailbox, $sort = 'id:desc', $offset = 0, $limit = 10) {
		
		$test = false;
		foreach($this->addresses as $akey => $addr)
			if($addressId == $addr['id']) {
				$test = true;
				break;
			}
		
		if(!$test) return false;
		
		if(array_key_exists($mailbox, $this->addresses[$akey]['mailboxes'])) {
			
			$sort = explode(':',$sort);
			$criteria = new Criteria();
			$criteria->addCondition('address_id', $addressId);
			$criteria->addCondition('mailbox', $this->addresses[$akey]['mailboxes'][$mailbox]['name']);
			$criteria->addOrderBy($sort[0], $sort[1]);
			$criteria->setLimit($offset, $limit);
			$messages = CommMessage::getByCriteria($criteria, CommMessage::TCLASS);
			
			return $messages;
		}
		
		return false; // invalid address or mailbox
	}*/
}
?>