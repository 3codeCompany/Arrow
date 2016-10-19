<?php

class Bean implements ActionBean{

	public function __construct(){

	}
	public function perform( RequestContext $request ){
		
		$parent = Folder::getByKey($request["folder"], Folder::TCLASS);

		foreach($request["file_action"] as $file => $action){
			if($action == "delete")
				unlink($file);
			elseif($action == "import"){
				MediaApi::createElement($parent, $file,"",$request["file_profile"][$file]);
			}	
			
		}
			

		
	}
	
	
}
?>