<?php
namespace Arrow\Access\Models;
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 14.08.12
 * Time: 22:46
 * To change this template use File | Settings | File Templates.
 */
//TODO porzadnie zaimplementowac i oprzec wszystkie wywolania uprawnien o managera
class AccessManager implements \Arrow\Models\IAccessManager
{
    private static $instance;

    public static function getDefault()
    {
        if (self::$instance == null)
            self::$instance = new AccessManager();
        return self::$instance;
    }

    public function getUser(){
        return Auth::getDefault()->getUser();
    }


    public static function turnOff()
    {
    }

    public static function turnOn()
    {
    }

    public static function isOn()
    {
        return true;
    }

    public function isLogged(){}
    public function isTemplateAccessible( \Arrow\Models\TemplateDescriptor $templateDescriptor ){
        return true;
    }
    public function isBeanAccessible( \Arrow\Models\BeanDescriptor $beanDescriptor ){
        return true;
    }

    public static function check()
    {
        return true;
    }

    public static function getActions()
    {
        return true;
    }


}
