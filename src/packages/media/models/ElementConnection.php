<?php
namespace Arrow\Media;

use Arrow\ORM\Persistent\PersistentObject;

class ElementConnection extends \Arrow\ORM\ORM_Arrow_Package_Media_ElementConnection {
    public function afterObjectCreate(PersistentObject $object)
    {

        $this[self::F_SORT] = $this->getPKey();
        $this->save();
        parent::afterObjectCreate($object);

    }


    public static function create( $initialValues, $class=self::TCLASS ){
		
		if(strpos($initialValues["element_id"], ";") !== false){
			$tmp = trim($initialValues["element_id"], ";");
			$elements = explode(";", $tmp);
			foreach($elements as $element){
				$initialValues["element_id"] = $element;
				ElementConnection::create($initialValues)->save();
			}
			
			return null;
		}
		
		if(isset($initialValues["delete_old"]) && $initialValues["delete_old"]){
	    	$criteria = new Criteria();
	        $criteria->addCondition(ElementConnection::F_MODEL, $initialValues["model"]);
	        $criteria->addCondition(ElementConnection::F_OBJECT_ID, $initialValues["object_id"] );
	        $criteria->addCondition(ElementConnection::F_NAME, $initialValues["name"]);
	        $conn = ElementConnection::getByCriteria($criteria, ElementConnection::TCLASS);
	        foreach($conn as $c) $c->delete();
	    }
	    unset($initialValues["delete_old"]);   
		
		$object = parent::create($initialValues, $class);
		$object->save();
		$object[self::F_SORT] = $object->getPKey();
		$object->save();
		return $object;
	}
	
	public function delete(){
		$element = \Arrow\ORM\Persistent\Criteria::query('\Arrow\Media\Element')->c('id', $this["element_id"])->findFirst();
		$direct = $this[self::F_DIRECT];
		//Track::createSpecialTrack($this[self::F_OBJECT_ID], $class, "delete_file", "usunięto plik: [{$element[Element::F_ID]}]<b>". $element[Element::F_NAME]."</b>" ) ;
		parent::delete();
		if($direct){
			if($element) $element->delete( false );
		}
	}
	//*END OF USER AREA*//
}
?>