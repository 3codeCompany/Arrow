<?php
namespace Arrow\Package\Media;
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
                "title" => "Media i pliki",
                "icon" => "icon-folder-open-alt",
                "id" => "org.arrowplatform.media",
                "elements" => array(
                    array("title" => "Przeglądaj", "template" => 'media::browser' , "class" => 'ico_products'),
                )
            )
        );
    }

    public static function addToSection()
    {
        return array(
            "org.arrowplatform.cms" => array(
                "title" => "Media i pliki",
                "elements" => array(
                    //array("title" => "Przeglądaj media", "template" => 'media::browser' , "class" => 'ico_products'),

                )
            )
        );
    }

    public static  function getDashboardElements()
    {
        return array(
            'main' => 'media::/dashboard/main'
        );
    }
}