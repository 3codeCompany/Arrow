<?php namespace Arrow\Models;
interface IStandardController {

	const TCLASS = __CLASS__ ;
	/**
	 *  Create new object of class given in $class with initial values from $initialValues. <br />
	 *	Note: Calling this function will not _add object to store. Use 'save' function to store the object.
	 * 
	 * Example:
	 * 
	 * <code>
	 *  $data = array(name=>"Prod1", category=>"Cat1"); <br />
	 * 	$product = Persistent::create($data,"Product");
	 * </code>
	 * 
	 * @param Array $initialValues - values to be stored in object
	 * @param String $class - name of class to instanciate
	 * @return Object	  
	 */	
	public static function create( $initialValues, $class = self::TCLASS );
	
	/**
	 * Returns object of given class and given id (Read from DB).
	 *
	 * Example:\n
	 * <code>
	 * 	$product = Persistent::getByKey(2,"Product");
	 * </code>
	 *
	 * @param int $key
	 * @param String $ObjectClass
	 * @return Object
	 */
	public static function getByKey( $key, $class );
	
	/**
	 * Returns array of objects of given class .
	 *
	 * If you want to return only part of objects stored in Database use Criteria object.<br />
	 *
	 * Example:
	 * 
	 * <code>
	 * 	$product = Persistent::getByCriteria( $criteria, "Product");<br />
	 * </code>
 	 *
	 * @param $criteria	 
	 * @param String $class
	 * @return Array 
	 */
	public static function getByCriteria( $criteria, $class );
	
	
	/**
	 * Saves object. If object already exists it should be updated in other case it should be added. 
	 *
	 * Example:
	 * 
	 * <code>
	 * 	$product->save();
	 * </code>
 	 *
	 * @return void
	 */
	public function save();
	
	/**
	 * Removes object.
	 *
	 * Note: This function does not remove any related objects.
	 *
	 * Example:
	 * 
	 * <code>
	 * 	$product->delete();
	 * </code>
 	 *
	 * @return void
	 */
	public function delete() ;
	
	/**
	 * Return primary key value of object.
	 * 
	 * @return int
	 */
	public function getPKey();
	
	/**
	 * Sets values of several properties in object
	 *
	 * @param array $values - array of key, value pairs to be set.
	 */	
	public function setValues( $values );
	
	
	/**
	 * SetValueof one property in object
	 * @param string $field - name of field
	 * @param mixed $value - value of field
	 * @param bool $tmp - if true field is temporary and is _add to object if false field is _add to object only if is in conf database file
	 */
	public function setValue( $field, $value, $tmp = false) ;
	
}
?>