<?php
namespace Arrow\CMS;


class CMSArticlesConnections extends Persistent{

	const TCLASS = __CLASS__;
	const F_ID = "id";
	const F_ARTICLE_ID = "article_id";
	const F_OBJECT_ID = "object_id";
	const F_MODEL = "model";


//*USER AREA*//

public static function create( $initialValues, $class=self::TCLASS ){
	$object = parent::create($initialValues, $class);
	return $object;
}

//*END OF USER AREA*//
}
?>