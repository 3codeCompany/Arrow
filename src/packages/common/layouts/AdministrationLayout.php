<?php
namespace Arrow\Common;
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

class AdministrationLayout extends \Arrow\Models\AbstractLayout
{

    private $breadcrumbGenerator;
    private $view;

    public function createLayout(ViewManager $manager)
    {

        $this->view = $manager->get();



        $title =  ConfigProvider::get("panel")["title"];// \Arrow\Models\Settings::getDefault()->getSetting("application.panel.title");
        $this->view ->assign("applicationTitle", $title);

        $user = Auth::getDefault()->getUser();

        if($user && $user->isInGroup("Developers"))
            $this->view->assign("developer", true);
        else
            $this->view->assign("developer", false);


        if(!isset($this->view["user"]))
            $this->view->assign("user", $user);

/*        try{
            $user[User::F_NEED_CHANGE_PASSWORD];
        }catch (\Exception $ex){
            Auth::getDefault()->doLogout();
            header("Location: /esotiq/access-/users/account");
            exit();
        }*/

        if( $user[User::F_NEED_CHANGE_PASSWORD] && $this->view->getPath() != "/users/account"){

            $v = \Arrow\Models\Dispatcher::getDefault()->get("access::/users/account");

            if(  \Arrow\Router::getDefault()->get() != $v ){
                \Arrow\Controller::redirectToView( $v );
                exit();
            }
        }

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

        return __DIR__."/admin/index.phtml";
    }


    public static function  page($content, $like = 'table'){

        return  (new HTMLNode($content,'div'))->addCSSClass('page page-'.$like) ;
    }

    public static function table($header, \Arrow\Controls\API\Table\Table $table, $buttons = []){
        $tableCode = $table->generate();

        $str = '<div class="panel-heading"><strong><span class="glyphicon glyphicon-th"></span> '.$header.'</strong></div>'.
        '<div class="table-filters">
        <div class="row">
            <div class="col-sm-1 col-xs-1 ">
            ';
        foreach($buttons as $button){
          $str.= $button->generate();
        }
        $str.=
        '
                        </div>
                        <div class="col-sm-5 col-xs-5">
                            <input type="text" placeholder="szukaj" class="form-control ng-pristine ng-valid" data-ng-model="searchKeywords" data-ng-keyup="search()">
                        </div>
                        <div class="col-sm-6 col-xs-6 ">
                                <div class="pull-right">
                                '.$table->generatePager(true).'
                                </div>
                        </div>
                    </div>
                </div>';


        $node = HTMLNode::createWithClass('panel panel-default table-dynamic', $str.$tableCode, 'div');
        return  $node;
    }


}

?>