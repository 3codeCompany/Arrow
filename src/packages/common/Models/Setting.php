<?php
/**
 * Created by PhpStorm.
 * User: IP
 * Date: 25.10.2016
 * Time: 15:04
 */

namespace Arrow\Common\Models;


use Arrow\ORM\ORM_Arrow_Application_Setting;

class Setting extends ORM_Arrow_Application_Setting
{

    public static function getSetting($name)
    {
        return Setting::get()
            ->_name($name)
            ->findFirst()
            ->_value();
    }

}