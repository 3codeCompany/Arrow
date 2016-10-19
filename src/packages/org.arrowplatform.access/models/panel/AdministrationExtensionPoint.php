<?php
namespace Arrow\Package\Access;
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 16.08.12
 * Time: 13:01
 * To change this template use File | Settings | File Templates.
 */
class AdministrationExtensionPoint extends \Arrow\Package\Common\AdministrationExtensionPoint
{
    public static function getElements()
    {
        return array(
            array(
                "title" => "Uprawnienia",
                "icon" => "icon-lock",
                "elements" => array(
                    array("title" => "Użytkownicy", "template" => 'access/users/list' , "icon" => "user"),
                    "---",
                    array("title" => "Grupy dostepu", "template" => 'access/groups/list'  , "icon" => "group"),
                    array("title" => "Uprawnienia", "template" => 'access/access/list'  , "icon" => "lock"),
                )
            )
        );
    }

    public static  function getDashboardElements()
    {
        return array(
            'main' => 'access/dashboard/main'
        );
    }


}