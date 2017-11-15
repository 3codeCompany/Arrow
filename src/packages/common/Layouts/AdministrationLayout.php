<?php

namespace Arrow\Common\Layouts;

use Arrow\ConfigProvider;
use Arrow\Controls\api\common\HTMLNode;
use Arrow\Controls\api\common\Icons;
use Arrow\Controls\api\WidgetsSet;
use Arrow\Models\Project;
use
    Arrow\ORM\Persistent\Criteria,
    Arrow\Access\Models\Auth,
    \Arrow\RequestContext,
    \Arrow\Access\Models\AccessAPI, \Arrow\Access\Models\User,
    Arrow\ViewManager;
use Arrow\ORM\Table;
use Arrow\Router;
use const ARROW_DOCUMENTS_ROOT;

class AdministrationLayout extends \Arrow\Models\AbstractLayout
{


    private $view;

    public function createLayout(ViewManager $manager)
    {

        $this->view = $manager->get();


        $title = ConfigProvider::get("panel")["title"];
        $this->view->assign("applicationTitle", $title);
        $title = ConfigProvider::get("panel")["icon"];
        $this->view->assign("applicationIcon", $title);

        $user = Auth::getDefault()->getUser();

        if ($user && $user->isInGroup("Developers")) {
            $this->view->assign("developer", true);
        } else {
            $this->view->assign("developer", false);
        }


        if (!isset($this->view["user"])) {
            $this->view->assign("user", $user);
        }


        if ($user[User::F_NEED_CHANGE_PASSWORD] && $this->view->getPath() != "/users/account") {

            $v = \Arrow\Models\Dispatcher::getDefault()->get("access::/users/account");

            if (\Arrow\Router::getDefault()->get() != $v) {
                \Arrow\Controller::redirectToView($v);
                exit();
            }
        }


        $manifest = false;
        $manifestFile = ARROW_DOCUMENTS_ROOT . "/assets/dist/webpack-assets.json";
        if (file_exists($manifestFile)) {
            $manifest = json_decode(file_get_contents($manifestFile), true);
        }

        $this->view->assign("webpackManifest", $manifest);


    }


    public function getLayoutFile()
    {

        return __DIR__ . "/admin/index2.phtml";
    }


}

?>