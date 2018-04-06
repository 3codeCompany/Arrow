<?php
namespace Arrow\Models;
use Arrow\RequestContext;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 04.09.12
 * Time: 13:23
 * To change this template use File | Settings | File Templates.
 */
interface IController
{
    public static function getInstance();
    public function eventRunBeforeAction(Action $view );
    public function eventRunAfterAction(Action $view );
    public function notFound(Action $action = null, RequestContext $request  = null);

}
