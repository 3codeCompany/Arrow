<?php
namespace Arrow\Models;
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 14.08.12
 * Time: 13:51
 * To change this template use File | Settings | File Templates.
 */
class Loader
{
    static protected $classes = array();

    public static function registerClasses($dir, $classArray)
    {
        self::$classes[$dir] = $classArray;
    }

    public final static function registerAutoload()
    {
        return spl_autoload_register(array(__CLASS__, 'includeClass'));
    }

    public final static function unregisterAutoload()
    {
        return spl_autoload_unregister(array(__CLASS__, 'includeClass'));
    }

    public final static function includeClass($class)
    {
        //print $class."\n";
        $cn = strtolower($class);
        foreach (self::$classes as $dir => $classes)
            if (isset($classes[$cn])) {
                require $dir . $classes[$cn];
            }
    }









}
