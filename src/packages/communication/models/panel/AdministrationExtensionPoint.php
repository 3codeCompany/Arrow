<?php
namespace Arrow\Communication;
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 16.08.12
 * Time: 13:01
 * To change this template use File | Settings | File Templates.
 */
class AdministrationExtensionPoint extends \Arrow\Common\AdministrationExtensionPoint
{
    public static function getElements()
    {
        return array(
            array(
                "title" => "Komunikacja",
                "icon" => "icon-comment",
                "id" => "org.arrowplatform.communication",
                "elements" => array(
                    array("title" => "Przesłane zapytania", "template" => 'communication::contact_form/list' , "icon" => "edit"),
                    array("title" => "Ustawienia email", "template" => 'communication::/mail/templates/list' , "icon" => "envelope-alt" )
                    //array("title" => "Lista newsletter", "template" => 'communication::newsletter/adressee/adresseeList' , "class" => 'ico_products'),
                )
            )
        );
    }

    public static  function getDashboardElements()
    {
        return array(
            'main' => 'communication::/dashboard/main'
        );
    }
}


