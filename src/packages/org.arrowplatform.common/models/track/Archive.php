<?php
namespace Arrow\Package\Common;
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 27.08.12
 * Time: 21:59
 * To change this template use File | Settings | File Templates.
 */
class Archive extends \Arrow\ORM\ORM_Arrow_Package_Common_Archive
{
    public function __construct($initialData = null){
        $initialData[self::F_DATE] = date( "Y-m-d H:i:s" );
        parent::__construct($initialData);
    }
}
