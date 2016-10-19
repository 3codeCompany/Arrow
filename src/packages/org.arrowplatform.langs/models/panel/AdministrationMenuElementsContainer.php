<?php
namespace Arrow\Langs\Utils;
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 16.08.12
 * Time: 13:01
 * To change this template use File | Settings | File Templates.
 */
class AdministrationMenuElementsContainer extends \Arrow\Package\Common\MenuElementsContainer
{
    public static function getElements()
    {
        $arr = array(
            array(
                    "title" => "Języki",
                    "elements" => array(
                )
            )
        );

        return $arr;
    }
}
