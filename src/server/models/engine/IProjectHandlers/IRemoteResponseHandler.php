<?php namespace Arrow\Models;

/**
 * Arrow project remote response handler interface
 * 
 * @version 1.0
 * @license  GNU GPL
 * @author Artur Kmera <artur.kmera@arrowplatform.org> 
 */
interface IRemoteResponseHandler extends \Arrow\Models\ISingleton {
	public function setHeaders();
	public function getResponse( $actionResult );
}

?>