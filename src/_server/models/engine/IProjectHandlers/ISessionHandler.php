<?php namespace Arrow\Models;

/**
 * Arrow project session handler interface
 * 
 * @version 1.0
 * @license  LGPL
 * @author 3code Team, Artur Kmera <artur.kmera@arrowplatform.org> 
 */
interface ISessionHandler extends \Arrow\Models\ISingleton {
	
	/**
	 * Regenerates session id
	 */
	public function regenerate() ;
	
}

?>