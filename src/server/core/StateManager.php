<?php 

class StateManager {
	
   /**
	 * setting the cookie
	 * exemple:
	 * 		setCoocie( "name", "value" ) ;
	 * 
	 * @param $name	- name of cookie
	 * @param $value - value of cookie
	 * @param $seconds - count seconds to remove cookie ( default: 3600 );
	 * @param $path - The path on the server in which the cookie will be available on ( default: "/" )
	 * @param $domain - The domain that the cookie is available. ( default: "" )
	 * @param $secure - if true cookie will be send if will be secure (https) connection ( default: false )
	 * @param $httponly - When TRUE the cookie will be made accessible only through the HTTP protocol ( default: false )
	 * @return nothing
	 */
	public static function setCookie( $name, $value = "" , $seconds = 3600, $path = "/", $domain = "" , $secure = false, $httponly = false ) {
		if( is_array($value) ) $value = serialize($value) ;
		setcookie( $name, $value, time() + $seconds, $path, $domain, $secure, $httponly ) ;
	}
	
	/**
	 * get cookie
	 * @param $name - name of the cookie
	 * @return value of cookie
	 */
	public static function getCookie( $name ) {
		$cookie = @unserialize( $_COOKIE[ $name ] ) ;
		if( $cookie === false ) return $_COOKIE[ $name ];
		return $cookie ;
	}
	
	/**
	 * removing the cookie
	 * @param $name - name of cookie to remove
	 * @return nothing
	 */
	public static function removeCookie( $name ) {
		setcookie( $name, "",time() - 3600 );
	}
	
}



?>