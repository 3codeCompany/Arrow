<?php
namespace Arrow\Package\Langs;


class Translation extends \Arrow\ORM\Persistent{

    const TCLASS = __CLASS__;
    const F_ID = "id";
    const F_ID_OBJECT = "id_object";
    const F_CLASS = "class";
    const F_LANG = "lang";
    const F_FIELD = "field";
    const F_VALUE = "value";


//*USER AREA*//

    public static function create( $initialValues, $class=self::TCLASS ){
        $object = parent::create($initialValues, $class);
        return $object;
    }

//*END OF USER AREA*//
}
?>