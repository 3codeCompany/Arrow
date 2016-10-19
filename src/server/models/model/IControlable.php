<?php namespace Arrow\Models;
interface IControlable{
	/**
	 * 
	 * @param int $type - IModelAction::ACTION_*
	 */
	public static function getActions( $model, $type = null );
} 
?>