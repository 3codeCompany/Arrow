<?php

namespace Arrow\Package\Communication;

use Arrow\ORM\Persistent\Criteria,
\Arrow\Package\Access\Auth,
\Arrow\ViewManager, \Arrow\RequestContext;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 04.09.12
 * Time: 14:20
 * To change this template use File | Settings | File Templates.
 */

class Controller extends \Arrow\Models\Controller
{
    public function actionRun($action, Action $view, RequestContext $request, $packageNamespace)
    {

    }

    public function dashboard_main( Action $view, RequestContext $request,$package){}

}