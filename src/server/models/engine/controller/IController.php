<?php

namespace Arrow\Models;

use Arrow\RequestContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 04.09.12
 * Time: 13:23
 * To change this template use File | Settings | File Templates.
 */
interface IController
{



    public function eventRunBeforeAction(Action $action);

    public function notFound(Action $action = null, RequestContext $request = null);
}
