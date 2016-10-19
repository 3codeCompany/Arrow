<?php



class Bean implements ActionBean{

	public function __construct(){

	}
	public function perform( RequestContext $request ){
		$el = MediaElement::getByKey($request["element_id"], MediaElement::TCLASS);
		$tmp = explode( "index", "http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
		$url = $tmp[0].str_replace("./", "",$el["path"]);
		
		$url = str_replace("localhost", "87.206.81.62", $url);
		
		$args = array(
			"action"=>"issuu.document.url_upload",
			"slurpUrl" => $url,
			"name" => str_replace(array("(", ")"), "_", $el["name"]),
			"title" => str_replace(array("(", ")"), "_", $el["name"]),
		);
		$link = $this->genLink($args);
		
		$content = file_get_contents($link);
		
		
		
		$result = json_decode($content, true);
		
		if( isset($result["rsp"]["_content"]["error"])){ 
			print json_encode($result["rsp"]["_content"]["error"]);
			exit();
		}
		
		MediaExport::create(array(
			MediaExport::F_ELEMENT_ID => $el->getPKey(),
			MediaExport::F_TYPE => "issuu",
			MediaExport::F_REMOTE_ID => $result["rsp"]["_content"]["document"]["documentId"]
		
		), MediaExport::TCLASS)->save();
		
		print json_encode($result["rsp"]["_content"]);
		\Arrow\Controller::end();
	}
	
	private function genLink($args){
		$args["apiKey"] = "7ql28kxircyuygi9haf78iy6yphtlr3a";
		$args["format"] = "json";
		
		ksort($args);
		
		
		$secret = "aachyg858p05oqy7mvq3dbdcatyy39o2";

		$sig = $secret;
		
		foreach($args as $name => $val)
			$sig.=$name.$val;
			
		$sig = md5($sig);
		$address = "http://api.issuu.com/1_0?signature={$sig}&".http_build_query($args);		
		
		return $address;
	}
}
?>