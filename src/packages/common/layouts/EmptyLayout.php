<?php
namespace Arrow\Common;

use
Arrow\ORM\Persistent\Criteria,
Arrow\Access\Models\Auth,
AccessManager,
\Arrow\RequestContext,
\Arrow\Access\Models\AccessAPI,
Arrow\ViewManager;
use Arrow\Router;

class EmptyLayout extends \Arrow\Models\AbstractLayout{


    public function createLayout(ViewManager $manager)
    {
        if(!isset($this->view["user"]))
            $manager->get()->assign("user", \Arrow\Access\Models\Auth::getDefault()->getUser());

        $this->view = $manager->get();

    }

     public function getLayoutFile()
    {
        $packages = \Arrow\Controller::$project->getPackages();
        return __DIR__."/EmptyLayout.phtml";
    }


    public function getAccessConf()
    {
        return array(
            "loginTemplate" => "access::auth/login"
            //"loginAddress" => "logowanie"
        );
    }

    //todo zmienic na standard

}

?>