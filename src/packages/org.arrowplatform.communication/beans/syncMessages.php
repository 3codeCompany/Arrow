<?php
ini_set("memory_limit","350M");


class Bean implements ActionBean{
	
	public function __construct(){
		
	}
	
	public function perform( RequestContext $request ){
		
		$mboxs = Auth::getDefault()->getUser()->getPostBoxConfigurations();
		$addresses = array();
		
		foreach( $mboxs as $mb ) {
			$flag = $mb["ssl"]? '/ssl/novalidate-cert' : '' ;
			
			//TODO pozostaw tylko pojedyńczy adres 
			//błąd w synchronizacji pojedyńczego adresu powoduje zatrzymanie synchronizacji pozostałych
			if(isset($request['address_id'])) {
				if($mb['id'] == $request['address_id'])
					$addresses[] = array('id' => $mb["id"], 'type' => 'imap', 'email' => $mb["email"], 'server' => $mb["server"], 'port' => $mb["port"], 'flags' => $flag, 'user' => $mb["login"], 'password' => $mb["password"] );
			}
			else $addresses[] = array('id' => $mb["id"], 'type' => 'imap', 'email' => $mb["email"], 'server' => $mb["server"], 'port' => $mb["port"], 'flags' => $flag, 'user' => $mb["login"], 'password' => $mb["password"] );
		}
		
		$items = 10;
		if(isset($request['items']))
			$items = $request['items'];
		
		$cmm = CommMessageManager::create($addresses);
		//var_dump($cmm->synchronize($items));
		//\Arrow\Controller::end();
		return array( "resp" => $cmm->synchronize($items));
	}
}
?>