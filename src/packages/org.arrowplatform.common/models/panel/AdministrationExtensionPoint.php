<?php
namespace Arrow\Package\Common;
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 16.08.12
 * Time: 13:01
 * To change this template use File | Settings | File Templates.
 */
class AdministrationExtensionPoint
{
    public static function getElements(){
        return array();
    }

    public static function setIgnoredElements(){
        return array();
    }
    public static function addToSection(){
        return array();
    }

    public static  function getDashboardElements(){
        return array();
    }

}
