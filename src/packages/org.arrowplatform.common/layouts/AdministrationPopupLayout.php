<?php
namespace Arrow\Package\Common;
use
    \Arrow\RequestContext,
    \Arrow\Package\Access\AccessAPI, \Arrow\Package\Access\Auth,
    \Arrow\ORM\Criteria,
    \Arrow\Controls\IFormContentProvider, \Arrow\Controls\IFormValuesSetter, Arrow\ViewManager;

class AdministrationPopupLayout extends \Arrow\Models\AbstractLayout
{

    private $langEditOn = false;
    private $view;
    private $breadcrumbGenerator;

    public function createLayout(ViewManager $manager)
    {

        $this->view = $manager->get();
        try{
            $title = \Arrow\Models\Settings::getDefault()->getSetting("application.panel.title");
            $this->view ->assign("applicationTitle", $title);
        }catch (\Arrow\Exception $ex){
            $this->view ->assign("applicationTitle", "CMS");
        }

        $this->view = $manager->get();
        $this->view->assign("developer", true);
        $user = Auth::getDefault()->getUser();

        $this->view->assign("user", $user);


    }

    public function setBreadcrumbGenerateor(BreadcrumbGenerator $generator)
    {
        $this->breadcrumbGenerator = $generator;
    }

    public function generateBreadcrumb()
    {
        if ($this->breadcrumbGenerator)
            return $this->breadcrumbGenerator->generate($this->view);
    }

    public function getLayoutFile()
    {
        $packages = \Arrow\Controller::$project->getPackages();
        return $packages["common"]["dir"] . "/layouts/AdministrationPopupLayout.phtml";
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
        $str .= '<h3><i class="fa fa-circle-arrow-right"></i>';
        if ($obj)
            $str .= $editText . " ";
        else
            $str .= $addText;
        $str .= "</h3>";
        $str .= '<div class="top-actions">';
        if ($obj)
            $str .= '<a class="save" href="#" ><i class="fa  fa-check"></i>Zatwierdź</a>';

        if ($editProtection && $obj)
            $str .= '<a class="edit-protection" href="#" ><i class="fa fa-ok-sign"></i>Kliknij aby edytować</a>';

        $str .= '<a class="save exit" href="#" ><i class="fa fa-circle-arrow-right"></i> Zatwierdź i zamknij</a><a class="cancel" href="#"  ><i class="fa fa-signout"></i> Anuluj</a>';

        return $str . '</div>';
    }

    public function renderLangPanel(){
        return "<div>lang</div>";
    }

    public function isLangEditOn()
    {
        return $this->langEditOn;
    }

    public function setLangEditOn($val)
    {
        $this->langEditOn = $val;
    }


}

?>