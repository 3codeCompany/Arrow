<?php
namespace Arrow\CMS;

use Arrow\ORM\PersistentFactory;
use Arrow\ORM\PersistentObject;

class Gallery extends \Arrow\ORM\ORM_Arrow_CMS_Gallery
{
    public function afterObjectCreate(PersistentObject $object)
    {
        parent::afterObjectCreate($object);
        if(empty($this["sort"])){
            $this["sort"] = $this->getPKey();
            PersistentFactory::save($this, false);
        }

    }


}

?>