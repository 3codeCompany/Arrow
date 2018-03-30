<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 04.10.13
 * Time: 11:53
 */

namespace Arrow\Common\Models\Allegro\Persistent;

use Arrow\ORM\ORM_App_Models_Allegro_Persistent_AllegroAuction;

class AllegroAuction extends ORM_App_Models_Allegro_Persistent_AllegroAuction {

    const STATUS_KEEP_UP = "Trwa";
    const STATUS_ENDED = "Zakończona";

    public function getAllegroUrl()
    {

        if($this->_sandbox())
            $address = 'http://allegro.pl.webapisandbox.pl/show_item.php?item=' . $this->_retItemId();
        else
            $address = 'http://allegro.pl/show_item.php?item=' . $this->_retItemId();

        return $address;
    }

}