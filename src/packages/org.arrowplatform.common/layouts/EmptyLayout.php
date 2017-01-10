<?php
namespace Arrow\Package\Common;

use
Arrow\ORM\Persistent\Criteria,
Arrow\Access\Auth,
Arrow\Access\AccessManager,
\Arrow\RequestContext,
\Arrow\Access\AccessAPI,
Arrow\ViewManager;
use Arrow\Router;

class EmptyLayout extends \Arrow\Models\AbstractLayout{


    public function createLayout(ViewManager $manager)
    {
        if(!isset($this->view["user"]))
            $manager->get()->assign("user", \Arrow\Access\Auth::getDefault()->getUser());

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
    public function printStandartMenu($addText, $editText, $obj, $objNameField = "name", $editProtection = false)
    {
        if ($obj) {
            if ($objNameField)
                $editText = $obj[$objNameField];
            else
                $editText = $obj->getFriendlyName();
        }
        $str = "";
        //$str.= "<span>{$obj[$objNameField]}</span>";
        $str .= '<h3><i class="icon-circle-arrow-right"></i>';
        if ($obj)
            $str .= $editText . " ";
        else
            $str .= $addText;
        $str .= "</h3>";
        $str .= '<div class="top-actions">';
        if ($obj)
            $str .= '<a class="save" href="#" ><i class=" icon-check"></i>Zatwierdź</a>';

        if ($editProtection && $obj)
            $str .= '<a class="edit-protection" href="#" ><i class="icon-ok-sign"></i>Kliknij aby edytować</a>';

        $str .= '<a class="save exit" href="#" ><i class="icon-circle-arrow-right"></i> Zatwierdź i zamknij</a><a class="cancel" href="#"  ><i class="icon-signout"></i> Anuluj</a>';

        return $str . '</div>';
    }
}

?>