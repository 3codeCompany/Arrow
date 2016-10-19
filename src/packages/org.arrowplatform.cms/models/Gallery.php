<?php
namespace Arrow\Package\CMS;

use Arrow\ORM\PersistentFactory;
use Arrow\ORM\PersistentObject;

class Gallery extends \Arrow\ORM\ORM_Arrow_Package_CMS_Gallery
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