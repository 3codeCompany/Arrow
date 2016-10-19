<?php
namespace Arrow\Package\CMS;
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
                "id" => "org.arrowplatform.cms",
                "title" => "Treści",
                "icon" => "icon-magic",
                "elements" => array(
                    array("title" => "Strony", "template" => 'cms::pages/structure', "icon" => "file-alt"  ),
                    //array("title" => "Galerie", "template" => 'cms::galleries/list'  , "icon" => 'picture'),
                    //array("title" => "Moduły serwisu", "template" => 'cms::pages/modules/list'  , "class" => 'ico_modules'),
                    //array("title" => "Bannery", "template" => 'cms::banners/list'  ),
                    array("title" => "Centrum zdarzeń", "template" => 'cms::news/list' , "icon" => 'quote-left' ),
                    //array("title" => "Miejsca strony", "template" => 'cms::pages/places/list'  , "class" => 'ico_places'),
                )
            )
        );
    }

    public static function setIgnoredElements()
    {
        return array("org.arrowplatform.media");
    }

}
