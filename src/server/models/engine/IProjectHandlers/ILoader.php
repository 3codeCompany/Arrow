<?php
namespace Arrow\Models;
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 14.08.12
 * Time: 12:06
 * To change this template use File | Settings | File Templates.
 */
interface ILoader
{
    public static function registerAutoload();
    public static function unregisterAutoload();
}
