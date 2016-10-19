<?php namespace Arrow\Models;

/**
 * Arrow project control object interface
 * 
 * @version 1.0
 * @license  GNU GPL
 * @author Artur Kmera <artur.kmera@arrowplatform.org> 
 */
interface IControlObject extends IUniqueObject{
	
	/**
	 * Returns container of object
	 * @return Container
	 */
	/*public function getContainer();*/
	
	/**
	 * Returns actions declared as access actions
	 * @return string[]
	 */
	public static function getActions();
	
	/**
	 * Returns access check for action
	 * 
	 * @param $tryier IUser
	 * @param $action string
	 * @return boolean
	 */
	public function checkAccess( IUser $tryier, $action );
}

?>