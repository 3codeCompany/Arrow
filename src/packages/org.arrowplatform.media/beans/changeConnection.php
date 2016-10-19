<?php


class Bean implements ActionBean{

	public function __construct(){

	}
	public function perform( RequestContext $request ){
		$request = RequestContext::getDefault();
		$criteria = new Criteria();
		$criteria->addCondition( MediaElementConnection::F_OBJECT_ID, $request["object_id"] );
				$criteria->addCondition( MediaElementConnection::F_MODEL, $request["model"] );
		$result = MediaElementConnection::getByCriteria($criteria, MediaElementConnection::TCLASS);
		foreach($result as $conn)
			$conn->delete();
		
		MediaElementConnection::create(array( 
			"object_id" => $request["object_id"], 
			"element_id" => $request["element_id"], 
			"model" => $request["model"],
			"name" => $request["name"],
			"direct" => $request["direct"]?$request["direct"]:0
		))->save();
	}
}
?>