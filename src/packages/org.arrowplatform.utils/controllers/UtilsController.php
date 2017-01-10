<?php

namespace Arrow\Package\Utils;

use Arrow\ORM\Persistent\Criteria,
    \Arrow\Access\Auth,
    \Arrow\ViewManager, \Arrow\RequestContext,
    \Arrow\Models\Operation,Arrow\Models\View;
use Arrow\Package\Common\AdministrationLayout;
use Arrow\Package\Common\EmptyLayout;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 04.09.12
 * Time: 14:20
 * To change this template use File | Settings | File Templates.
 */

class UtilsController extends \Arrow\Models\Controller
{


    public function settings_list(Action $view, RequestContext $request, $package)
    {

        $view->setLayout(new AdministrationLayout(),new EmptyLayout());

        $config = \Arrow\Models\Settings::getDefault()->getConfiguration();
        $packages = \Arrow\Controller::$project->getPackages();

        $view->assign("config", $config);
        $view->assign("packages", $packages);

    }

    public function settings_save_settings(IAction $action, RequestContext $request)
    {
        $handle = \Arrow\Models\Settings::getDefault();
        foreach ($request["data"] as $package => $settings) {
            foreach ($settings as $name => $setting) {
                $handle->setSettingValue($package, $name, $setting);
            }
        }

        if (isset($_FILES["data"]["name"])) {
            foreach ($_FILES["data"]["name"] as $package => $settings) {
                foreach ($settings as $name => $setting) {
                    $truePath = $_FILES["data"]["name"][$package][$name];
                    $file = $_FILES["data"]["tmp_name"][$package][$name];
                    if($file){
                        $element = \Arrow\Package\Media\Element::createFromFile('application.config', $file, $name, true, $truePath);
                        $handle->setSettingValue($package, $name, $element["path"]);
                    }
                }
            }
        }

        \Arrow\Controller::$project->clearCache();

    }


    public function developer_clear_cache(IAction $action, RequestContext $request)
    {
        \Arrow\Controller::$project->clearCache();
    }

    public function utils_developer_console(Action $view, RequestContext $request, $package)
    {
        $file = ARROW_CACHE_PATH . "/last_dev_console.txt";
        if (file_exists($file))
            $code = file_get_contents($file);
        else
            $code = "print 'Hello';";

        $view->assign("code", $code);

    }

    public function utils_developer_console_execute(Action $view, RequestContext $request, $package)
    {
        Console::init();
    }

    public function developer_saveConsoleCode(IAction $action, RequestContext $request)
    {
        file_put_contents(ARROW_CACHE_PATH . "/last_dev_console.txt", $request["code"]);
    }

}