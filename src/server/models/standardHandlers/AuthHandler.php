<?php namespace Arrow\Models;

/**
 * The simplest AuthHandler to user admin/admin
 * @author 3code group
 *
 */
class AuthHandler implements IAuthHandler{

	/**
	* Object instance keeper
	*
	* @var Auth
	*/
	private static $oInstance = null;

	/**
	* User object
	*
	* @var User
	*/
	public $user = null;

	/**
	* Singleton IAuthHandlerImplementation
	*
	* @return Auth
	*/
	public static function getDefault(){
		if( self::$oInstance == null ){
			self::$oInstance = new AuthHandler();
		}
		return self::$oInstance;
	}


	/**
	 * Unserialize user from session
	 * 
	 */
	private function __construct(){
		if( isset( $_SESSION["auth"] ) && is_string( $_SESSION["auth"] ) )
		$this->user = unserialize($_SESSION["auth"]);
	}


	/**
	* Getter for user object
	*
	* @return User
	*/
	public function getUser(){
		return $this->user;
	}



	/**
	* Login
	*
	* @param String $login
	* @param String $password
	*
	* @return boolean
	*/

	public function doLogin( $login, $password ){

		if( $login == "admin" && $password == "admin" ) {
        $user["login"] = $login ;
        $user["password"] = $password ;
        $this->user = $user;
        $_SESSION["auth"] = serialize($user);
        return true;
		}
    return false ;
	}

	/**
	* logout
	*
	* @return void
	*/

	public function doLogout(){
		unset($_SESSION["auth"]);
	}


	/**
	* IAuth handler implementation
	*
	* @return boolean
	*/

	public function isLogged(){
		return !is_null($this->user);
	}

	/**
	* IAuth handler implementation
	*
	* @param unknown_type $examinedObject
	* @return boolean
	*/
	public function checkAccess( $examinedObject ){
		return true ;
	}

}