<?php
namespace Arrow\Models;
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 01.09.12
 * Time: 11:54
 * To change this template use File | Settings | File Templates.
 */
interface IAccessManager
{
    public static function getDefault();
    public function getUser();
    public function isLogged();
    public function isTemplateAccessible( \Arrow\Models\TemplateDescriptor $templateDescriptor );
    public function isBeanAccessible( \Arrow\Models\BeanDescriptor $beanDescriptor );

}
