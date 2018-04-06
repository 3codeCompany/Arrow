<?php namespace Arrow\Models;
/**
 * Arrow template exception
 * 
 * @version 1.0
 * @license  GNU GPL
 * @author Artur Kmera <artur.kmera@arrowplatform.org> 
 */
 
 class AccessException extends \Arrow\Exception {

	private $objectName = "Arrow Access Exception Handler";

	private $errors = array (0 => "Extension.",);

	public function __construct( $error, $errorCode = 0) {

		parent :: __construct( $error, $errorCode);

	}

}
?>
