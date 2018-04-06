<?php

namespace Arrow\Common\Controllers;

use Arrow\Controls\api\common\HTMLNode;
use Arrow\Controls\api\common\Icons;
use Arrow\Controls\API\Components\Toolbar;
use Arrow\Controls\API\Forms\Fields\Select;
use Arrow\Controls\API\Forms\Fields\Text;
use Arrow\Controls\API\Forms\Form;
use Arrow\Controls\api\Layout\LayoutBuilder;
use Arrow\Controls\api\SerenityJS;
use Arrow\Models\Action;
use Arrow\Models\Operation;
use Arrow\ORM\Persistent\Criteria,
\Arrow\Access\Models\Auth,
\Arrow\ViewManager, \Arrow\RequestContext, Arrow\Models\View;
use Arrow\Router;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 04.09.12
 * Time: 14:20
 * To change this template use File | Settings | File Templates.
 */

class Controller extends \Arrow\Models\Controller
{


    public function index( Action $view, RequestContext $request){
        $view->setLayout( new AdministrationLayout(), new EmptyLayout());





        //if($this->u)
    }

    public function error( Action $view, RequestContext $request){}

    public function history( Action $view, RequestContext $request){
        $object = Criteria::query($request["model"])
            ->findByKey($request["object_id"]);
        $track = Criteria::query(Track::getClass())
            ->c(Track::F_CLASS, $request["model"])
            ->c(Track::F_OBJECT_ID, $request["object_id"])
            ->order(Track::F_DATE, Criteria::O_DESC)
            ->join(\Arrow\Access\Models\User::getClass())
            ->find();

        $view->assign("track", $track);
        $view->assign("object", $object);

        $processInfo = function($string){
            $serialized = (@unserialize($string) !== false);
            if($serialized){
                $array = unserialize($string);
                $tmp = array();
                foreach($array as $index => $value){
                    $tmp[] = $index.":".$value;
                }
                $string = implode(", ",$tmp);
            }
            return $string;
        };
        $view->assign("processInfo", $processInfo);
    }

    public function part_header( Action $view, RequestContext $request ){
        $view->setLayout(new EmptyLayout());

        try{
            $title = \Arrow\Models\Settings::getDefault()->getSetting("application.panel.title");
            $view ->assign("applicationTitle", $title);
        }catch (\Arrow\Exception $ex){
            $view ->assign("applicationTitle", "CMS");
        }
    }

    public function settings_list( Action $view, RequestContext $request ){
        $view->setLayout(new AdministrationLayout(), new EmptyLayout());
        $form = Form::_new("settings")
            ->setAction(Router::link("./change"))
            ->setNamespace(false)
            ->on(Form::EVENT_SUCCESS, "SerenityCommon.info('Zapisano ustawienia')");

        $config = \Arrow\Models\Settings::getDefault()->getConfiguration();
        $packages = \Arrow\Controller::$project->getPackages();

        $lay = LayoutBuilder::create()
            ->insert(Toolbar::_new("Ustawienia",[$form->getSubmit()->addCSSClass("btn-link")->prepend(Icons::icon(Icons::CHECK_CIRCLE)." ")]))
            ->form($form);

        $lay->tabSet();
        $currentSection = "";
        foreach($config as  $package => $settings) {
            $lay->tab($packages[$package]["name"]);
            foreach ($settings as $setting) {
                if(!$setting["editable"]){continue;}
                if($currentSection!=$setting["section"]){
                    $currentSection = $setting["section"];
                    $lay->section($setting["section"]);
                }
                $name = "data[{$setting["package"]}][{$setting["name"]}]";
                if($setting["type"] == "set"){
                    $field = Select::_new($name,$setting["options"],$setting["value"]);
                }else{
                    $field = Text::_new($name,false,$setting["value"]);
                }

                $lay->formField($setting["title"]?$setting["title"]:$setting["name"], $field );

}


            $lay->tabEnd();

        }
        $lay->tabSetEnd();
        $lay->formEnd();

        $view->setGenerator($lay);
    }


    public function settings_change( $action, RequestContext $request)
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
        $this->json([true]);

    }


    //todo sprawdzic wykorzystanie
    public function translations_saveTmpText( Operation $operation, RequestContext $request){
        $file = "./data/cache/tmp_cache_file".md5($request["text"].microtime()).".txt";
        file_put_contents($file, $request["text"] );
        return $file;
        return 'http://'.$_SERVER["HTTP_HOST"].Router::getBasePath().str_replace(PATH_SEPARATOR,"/",$file);
    }
    //todo sprawdzic wykorzystanie
    public function translations_openTempValue( Action $view, RequestContext $request ){
        $view->setLayout(new EmptyLayout());
        $view->assign("text", file_get_contents($request["file"]));
    }

    public function navigation( Action $view ){
        $view->setLayout(new EmptyLayout());
    }

}