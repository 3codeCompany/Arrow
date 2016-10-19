<?php

class Bean implements ActionBean{

	public function __construct(){

	}
	public function perform( RequestContext $request ){
		CommNewsletterQueue::proccess();
	}
}
?>