<?php

namespace Arrow\Common\Layouts;

use Arrow\Access\Models\Auth;
use Arrow\Models\Action;

use Arrow\ViewManager;


class ReactComponentLayout extends \Arrow\Models\AbstractLayout
{
    private $breadcrumbGenerator;
    private $view;

    public function createLayout(ViewManager $viewM)
    {

        $this->view = $viewM->get();
        $viewM->get()->assign("path", $viewM->get()->getPath());

        //$view->assign("path",$view->getPath());

        if (!isset($_SESSION["inDev"])) {
            $_SESSION["inDev"] = false;
        }
        if (isset($_REQUEST["inDev"])) {
            $_SESSION["inDev"] = $_REQUEST["inDev"];
        }

        $user = Auth::getDefault()->getUser();

        if ($user->isInGroup("Developers")) {
            $viewM->get()->assign("developer", true);
        } else {
            $viewM->get()->assign("developer", false);
        }

        $viewM->get()->assign("user", $user);

        /*        try{
                    $user[User::F_NEED_CHANGE_PASSWORD];
                }catch (\Exception $ex){
                    Auth::getDefault()->doLogout();
                    header("Location: /esotiq/access-/users/account");
                    exit();

                }*/

        $manifest = false;
        $manifestFile = ARROW_DOCUMENTS_ROOT . "/assets/dist/webpack-assets.json";
        if (file_exists($manifestFile)) {
            $manifest = json_decode(file_get_contents($manifestFile), true);
        }

        $this->view->assign("webpackManifest", $manifest);




    }

    public function setBreadcrumbGenerateor( BreadcrumbGenerator $generator ){
        $this->breadcrumbGenerator = $generator;
    }

    public function generateBreadcrumb( ){
        if($this->breadcrumbGenerator)
            return $this->breadcrumbGenerator->generate( $this->view);
    }

    public function getLayoutFile()
    {
        return __DIR__ . "/ReactComponentLayout.phtml";
    }

    public function getFileName($path)
    {
        return $path . ".component.js";
    }

    public function getFirstTemplateContent( Action $action)
    {
        return "";
    }
}