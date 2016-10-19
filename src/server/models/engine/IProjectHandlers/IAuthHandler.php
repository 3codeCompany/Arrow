<?php namespace Arrow\Models;
namespace Arrow\Models;

/**
 * Arrow project auth handler interface
 * 
 * @version 1.0
 * @license  GNU GPL
 * @author Artur Kmera <artur.kmera@arrowplatform.org> 
 */
interface IAuthHandler extends \Arrow\Models\ISingleton{

  /**	 
   * Returns auth state	 
   * @return boolean	 
  */	


  public function isLogged();
	
	/**
	 * Returns user object
	 * 
	 * @return IUser
	 */
	public function getUser();
}

?>