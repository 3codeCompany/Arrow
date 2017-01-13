<?php

namespace Arrow\Access;

use Arrow\Controls\api\common\AjaxLink;
use Arrow\Controls\api\common\BreadcrumbElement;use Arrow\Controls\api\common\ContextMenu;
use Arrow\Controls\api\common\Events;
use Arrow\Controls\api\common\HTML;
use Arrow\Controls\api\common\HTMLNode;
use Arrow\Controls\api\common\Icons;
use Arrow\Controls\api\common\Link;
use Arrow\Controls\API\Components\Breadcrumb;use Arrow\Controls\API\Components\MultiFile;
use Arrow\Controls\API\Components\Toolbar;
use Arrow\Controls\API\FilterPanel;use Arrow\Controls\api\Filters\SelectFilter;use Arrow\Controls\API\FiltersPresenter;use Arrow\Controls\API\Forms\Fields\Hidden;
use Arrow\Controls\API\Forms\Fields\Select;
use Arrow\Controls\API\Forms\Fields\SwitchF;
use Arrow\Controls\API\Forms\Fields\Text;
use Arrow\Controls\API\Forms\Fields\Textarea;
use Arrow\Controls\API\Forms\FieldsList;
use Arrow\Controls\API\Forms\Form;
use Arrow\Controls\API\Forms\Validator;
use Arrow\Controls\api\Layout\LayoutBuilder;
use Arrow\Controls\api\SerenityJS;
use Arrow\Controls\API\Table\ColumnList;
use Arrow\Controls\API\Table\Columns\Editable;
use Arrow\Controls\API\Table\Columns\Simple;
use Arrow\Controls\API\Table\Columns\Template;
use Arrow\Controls\api\WidgetsSet;

use Arrow\Models\Dispatcher;
use Arrow\Models\IAction;
use Arrow\Models\Project;

use Arrow\Package\Application\Language;
use Arrow\Common\AdministrationLayout;
use Arrow\Common\AdministrationPopupLayout;
use Arrow\Common\EmptyLayout;
use Arrow\Common\FormHelper;
use Arrow\Common\Links;
use Arrow\Common\PopupFormBuilder;
use Arrow\Common\TableDataSource;
use Arrow\Common\TableHelper;
use Arrow\Router;
use Arrow\Controls\API\Table\Table;
use
    \Arrow\RequestContext,
    \Arrow\ORM\Persistent\Criteria,
    Arrow\Common\Track,
    Arrow\Models\Operation, Arrow\Models\Action;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 04.09.12
 * Time: 14:20
 * To change this template use File | Settings | File Templates.
 */

class AccessController extends \Arrow\Models\Controller
{

    public function __construct()
    {
        //AccessAPI::checkInstallation();
    }


    public function auth_login(Action $view, RequestContext $request)
    {
        $path = RequestContext::getProtocol().$_SERVER["HTTP_HOST"].str_replace([ARROW_DOCUMENTS_ROOT, DIRECTORY_SEPARATOR], ["","/"],__DIR__);
        $path.= "/../../org.arrowplatform.common/layouts/admin/";


        if(Auth::getDefault()->isLogged()){
            header("Location: ".(RequestContext::getBaseUrl())."admin");
            exit();
        }

        $this->view->assign("layoutPath", $path);

        $view->setLayout(new EmptyLayout(), new EmptyLayout());

        User::F_ID;
        $form = Form::_new("login")
            ->setAction( Router::link("access/auth/loginAction") )
            ->setNamespace("data");


        //$form->on(Form::EVENT_SUCCESS, SerenityJS::hash("#{context.redirectTo}"));
        $form->on(Form::EVENT_SUCCESS, SerenityJS::js("window.location.href = '/#{context.redirectTo}'; return false;"));

        $view->assign("form", $form);

        try {
            $title = \Arrow\Models\Settings::getDefault()->getSetting("application.panel.title");
            $view->assign("applicationTitle", $title);
        } catch (\Arrow\Exception $ex) {
            $view->assign("applicationTitle", "Application");
        }

        try {
            AccessAPI::checkInstallation();
        } catch (\Arrow\Exception $ex) {
            AccessAPI::setup();
            exit("Access API setup finish");
        }
    }

    public function auth_loginAction(IAction $action, RequestContext $request)
    {
        $validator = Validator::create($request["data"])
            ->required(["login", "password"]);

        if( !$validator->check() )
            $this->json( $validator->response() );


        $authHandler = Auth::getDefault();
        $authHandler->doLogout();
        $authHandler->doLogin($request["data"]["login"], $request["data"]["password"]);


        if(!$authHandler->isLogged()){
            $validator->addError("Nieprawidłowy login lub hasło");
            $this->json( $validator->response() );
        }




        $this->json( ["redirectTo" => trim($request["from"]?$request["from"]:Router::getBasePath()."/admin", "/" )]);


    }


    public function auth_logout(IAction $action, RequestContext $request)
    {
        $authHandler = Auth::getDefault();
        $authHandler->doLogout();
        $this->back();
    }


    public function dashboard_main(Action $view, RequestContext $request)
    {
    }

    public function users_account(Action $view, RequestContext $request)
    {
        $view->setLayout(new AdministrationLayout(), new EmptyLayout());
        /** @var User $user */
        $user = Auth::getDefault()->getUser();

        $ds = TableDataSource::fromClass(Track::getClass())
            ->c(Track::F_CLASS, User::getClass())
            ->c(Track::F_OBJECT_ID, $user["id"])
            ->c(Track::F_ACTION, "login")
            ->limit(0, 10);
            //->order("id", Criteria::O_DESC);

        $cols = ColumnList::create()
            ->addColumn(Simple::_new(Track::F_DATE));
        $table = Table::create("lastLogin", $ds, $cols);


        $l = LayoutBuilder::create()
            ->panel("Twoje konto",Icons::USER);


        $form = Form::_new("editAccount",$user)
            ->setAction(Router::link("./accountSave"))
            ->on(Form::EVENT_SUCCESS, "alert('Zapisano');")
            ->setNamespace("data");

        $l

            ->tabSet()
            ->tab("Dane konta")
            ->form($form)
            ->formText("Login", $user->_login())


            /*->row()
            ->formField("Dział", Text::_new(User::F_EMAIL, "Dział"),[LayoutBuilder::CONF_COLUMNS => 4])
            ->formField("Stanowisko", Text::_new(User::F_EMAIL, "Email"),[LayoutBuilder::CONF_COLUMNS => 4])
            ->rowEnd()*/
            ->formField("Email", Text::_new(User::F_EMAIL, "Email"),[LayoutBuilder::CONF_COLUMNS => 4])
            //->formField("Tel. stacjonarny", Text::_new(User::F_EMAIL, "Tel. stacjonarny"),[LayoutBuilder::CONF_COLUMNS => 3])
            //->formField("Tel. komórkowy", Text::_new(User::F_EMAIL, "Tel. komórkowy"),[LayoutBuilder::CONF_COLUMNS => 3])

            ->formField("Zmień hasło", Text::_new(User::F_PASSWORD, "Zostaw puste jeśli nie zmieniasz")->setValue(""),[LayoutBuilder::CONF_COLUMNS => 4])
            ->row()
                ->label("Avatar")
                ->col2(MultiFile::_new("photo", $user))
            ->rowEnd()
            ->add($form->getSubmit(),["offset" => 2])
            ->separator()

            ->formEnd()
            ->tabEnd()
            //->tab("Ostatnie logowania")
            ->tabSetEnd()
        ;



        $view->setGenerator(AdministrationLayout::page($l));
    }

    public function users_accountSave($op, RequestContext $request){
        $user = Auth::getDefault()->getUser();
        $user->setValues($request["data"]);
        $user->save();
        $this->json();
    }

    public function auth_change_password(IAction $action, RequestContext $request)
    {
        $user = Auth::getDefault()->getUser();


        $user[User::F_PASSWORD] = $request["data"]["pass"];
        $user[User::F_NEED_CHANGE_PASSWORD] = 0;
        $user->save();

    }

    public function validateChangePassword($proposedValues, $formData, $connector)
    {

        if ($proposedValues["pass"] != $proposedValues["repass"]) {
            $connector->addFieldError("repass", "Wprowadzone hasła nie zgadzają się");
        }

        if (strlen($proposedValues["pass"]) < 8) {
            $connector->addFieldError("pass", "Hasło musi posiadać co najmniej 8 znaków");

        }


        preg_match_all("/[A-Z]/", $proposedValues["pass"], $match);
        $upperCase = count($match [0]);
        if ($upperCase == 0) {
            $connector->addFieldError("pass", "Hasło musi posiadać co najmniej 1 dużą literę");
        }

        preg_match_all("/[0-9]/", $proposedValues["pass"], $match);
        $digs = count($match [0]);
        if ($digs < 2) {
            $connector->addFieldError("pass", "Hasło musi posiadać co najmniej 2 cyfry");
        }


    }

    public function users_list(Action $view, RequestContext $request)
    {
        $ds = TableDataSource::fromClass(User::getClass());

        $filterPanel = FilterPanel::create() 
            ->addSection("Użytkownik",[
                SelectFilter::create("access_group","Grupa dostępu")
                    ->setContent([ 0 => "---"  ] + AccessGroup::get()->_id(1, Criteria::C_NOT_EQUAL)->findAsFieldArray(AccessGroup::F_NAME,true)),
        ]);

        $filters = $filterPanel->getFilterValues();
        $options = $filterPanel->getFilterOptions();
        foreach( $filters as $field => $value){
            if($value){
                if($field == "access_group"){
                    $ds->_join(AccessUserGroup::getClass(),["id" => AccessUserGroup::F_USER_ID],"AG",["id"]);
                    $ds->c("AG:".AccessUserGroup::F_GROUP_ID, $value);
                }
            }
        }

        $l = LayoutBuilder::create();
        $l->col12(Toolbar::_new([
                Breadcrumb::create([
                "System",
                BreadcrumbElement::create("Użytkownicy")->setActive(1)
                ]),
                Link::_new("Dodaj")
                    ->on(Link::EVENT_CLICK, SerenityJS::hash(Router::link("./edit")).SerenityJS::returnFalse())
                    ->addLinkCSSClass("btn btn-primary")
            ]));

        $table = Table::create("users" ,$ds)
            ->setContextData(["id"])
            ->on(Table::EVENT_ROW_CLICKED, SerenityJS::hash(Router::link("./edit?key=#{context.id}" )));
        $table->getColumnsList()
            ->addColumn(Simple::_new("id","id")->setWidth(70))
            ->addColumn(Editable::_boolswitch("active","Aktywny", Router::link("./save?activity=1") ,["id"])->setWidth(60))
            ->addColumn(Simple::_new("login","login"))
            ->addColumn(Simple::_new(User::F_EMAIL,"Email"))
            ->addColumn(Template::_new(function( User $context){
                return implode(",",$context->getAccessGroups());
            },"Grupy dostępu"))
            ->addColumn( Template::_new(function( User $context) use ($table){
                return ContextMenu::_new( [
                    Link::_new(Icons::icon(Icons::PENCIL)." Edytuj")->on(Link::EVENT_CLICK, SerenityJS::hash(Router::link("./edit?key=".$context->_id() ))),
                    ContextMenu::separator(),
                    AjaxLink::_new(Icons::icon(Icons::TRASH_O)." Usuń", Router::link("./delete?key=".$context->_id()))
                        ->setSuccessInformation("Usunięto")
                        ->setConfirmQuestion("Czy napewno usunąć `{$context->_login()}`?")
                        ->setSuccessRefresh($table->getId())
                    ] )->generate();
            }, "Opcje"))

        ;

        $table->prependWidged(FiltersPresenter::create([$table,$filterPanel]));
        $filterPanel->addConnectedWidget($table);

        $l->add($table);
        $l->insert($filterPanel);
        $l->insert($filterPanel->getOpenButton());
        $view->setGenerator(AdministrationLayout::page( new WidgetsSet([ $l])));

    }

    public function users_delete( $view, RequestContext $request){
        $user = User::get()->findByKey($request["key"]);
        $user->delete();
        $this->json([1]);
    }
    public function users_edit(Action $view, RequestContext $request)
    {
        $view->setLayout( new EmptyLayout());

        $user = User::get()->findByKey($request["key"]);
        $form = Form::_new("edit",$user)
            ->setNamespace("data")
            ->addHiddenField("key", $request["key"])
            ->setAction(Router::link("./save"))
            ->on(Form::EVENT_SUCCESS, SerenityJS::back());

        $groups = Criteria::query(AccessGroup::getClass())->findAsFieldArray('name', true);
        $selectedGroups = [];
        if($user){
            $selectedGroups = AccessUserGroup::get()
                ->c("user_id", $user->getPKey())
                ->findAsFieldArray('group_id');
        }

        $list = LayoutBuilder::create()
           ->insert(Toolbar::_new([
                Breadcrumb::create([
                    "System",
                    BreadcrumbElement::create("Użytkownicy",SerenityJS::hash(Router::link("./list"))),
                    BreadcrumbElement::create($user?$user->_login():"Nowy")->setActive(1)
                ]),
                $form->getSubmit(),
                Link::_new("Anuluj")
                ->on(Link::EVENT_CLICK, SerenityJS::back())
                ->addCSSClass("btn btn-default")

            ]))
            ->form($form)
            ->panel("Dane podstawowe", Icons::USER)
            ->formField("Login", Text::_new(User::F_LOGIN));


        if(class_exists("Language")){
            $langs = Language::get()->find();

            if($langs->count() > 1){
                $tmp = [];

                foreach($langs as $l){
                    $tmp[$l->_code()] = $l->_name();
                }
                $keys = array_keys($tmp);
                if(defined("User::F_LANG"))
                    $list->formField("Język", SwitchF::_new(User::F_LANG, $tmp, reset($keys)));
            }
        }

        $list

            ->formField("Email", Text::_new(User::F_EMAIL))
            //->formField("External id", Text::_new(User::F_EXTERNAL_ID))
            ->formField("Aktywny", SwitchF::_bool(User::F_ACTIVE,1))
            ->formField(null,Hidden::_new("accessGroups][","")->setNamespace("parameters"))
            ->formField("Grupy dostępu",
                Select::_multi("accessGroups", $groups)
                    ->setValue($selectedGroups)
                    ->setNamespace("parameters")
                    ->setWidth(880)

            )
            ->sectionEnd()
            ->panelEnd()
            ->panel("Zmiana hasła", Icons::KEY)

            ->formField("Hasło", Text::_new(User::F_PASSWORD)->setValue(""))
            ->formField("Powtórz", Text::_new("repassword")->setNamespace(""))
            ->sectionEnd()
            ->panelEnd()
            ->formEnd();




        $view->assign("generator", AdministrationLayout::page($list));

    }

    public function users_save( $view, RequestContext $request){
        if($request["activity"]){
            $user = User::get()
                ->findByKey($request["id"]);

            $user[User::F_ACTIVE] = $user->_active()?0:1;
            $user->save();
            $this->json([1]);
        }

        if($request["key"]) {
            User::get()
                ->findByKey($request["key"])
                ->setValues($request["data"])
                ->setParameters($request["parameters"])
                ->save();
        }else{
            $u = User::create($request["data"]);
            $u->setParameters($request["parameters"])
                ->save();

        }

        $this->json([1]);
    }



    public function groups_list(Action $view, RequestContext $request)
    {
        $view->setLayout( new EmptyLayout());

        $ds = TableDataSource::fromClass(AccessGroup::getClass())
            ->c("id",4,Criteria::C_GREATER_THAN);

        $list = ColumnList::create()
            ->addColumn(Simple::_new("id", "id"))
            ->addColumn(Simple::_new("name", "Nazwa"))
            ->addColumn( Template::_new(function( AccessGroup $context) {
                return ContextMenu::_new( [
                    Link::_new(Icons::icon(Icons::PENCIL)." Edytuj")->on(Link::EVENT_CLICK, SerenityJS::hash(Router::link("./edit?key=".$context->_id() ))),
                    ContextMenu::separator(),
                    AjaxLink::_new(Icons::icon(Icons::TRASH_O)." Usuń", Router::link("./delete?key=".$context->_id()))
                        ->setSuccessInformation("Usunięto")
                        ->setConfirmQuestion("Czy napewno usunąć `{$context->_name()}`?")
                        ->setSuccessRefresh("groups")
                ] )->generate();
            }, "Opcje"));


        $table =  Table::create("groups",$ds,$list)

        ->setContextData(["id"])
        ->on(Table::EVENT_ROW_CLICKED, SerenityJS::hash(Router::link("./edit?key=#{context.id}" )));;

        $l = LayoutBuilder::create();
        $l->insert(Toolbar::_new( [
            Breadcrumb::create([
                    "System",
                    BreadcrumbElement::create("Grupy dostępu")->setActive(1)
                ]),
            Link::_new("Dodaj")
                ->on(Link::EVENT_CLICK, SerenityJS::hash(Router::link("./edit")).SerenityJS::returnFalse())
                ->addLinkCSSClass("btn btn-primary")]));
        $l->add($table);


        $view->assign("generator", AdministrationLayout::page( $l ));
    }

    public function groups_delete( $view, RequestContext $request){
        AccessGroup::get()->findByKey($request["key"])->delete();

        $this->json([1]);
    }


    public function groups_edit(Action $view, RequestContext $request)
    {
        $view->setLayout( new EmptyLayout());
        $group = AccessGroup::get()->findByKey($request['key']);
        $form = Form::_new("edit",$group)
            ->setNamespace("data")
            ->addHiddenField("key", $request["key"])
            ->setAction(Router::link("./save"))
            ->on(Form::EVENT_SUCCESS, SerenityJS::back());

        $list = LayoutBuilder::create()
            ->form($form)
            ->panel("Edycja grupy dostępu", Icons::USERS)
            ->formField("Nazwa", Text::_new("name"))
            ->formField("Opis", Textarea::_new("description"))
            ->panelEnd()
            ->formEnd();

        $list->add(Toolbar::_new(null,[
            $form->getSubmit(),
            Link::_new("Anuluj")
                ->on(Link::EVENT_CLICK, SerenityJS::back())
                ->addCSSClass("btn btn-default")
        ]));

        $view->assign("generator", AdministrationLayout::page( $list));

    }
    public function groups_save( $view, RequestContext $request)
    {
        if ($request["key"])
            AccessGroup::get()->findByKey($request["key"])->setValues($request["data"])->save();
        else{
            AccessGroup::create($request["data"]);
        }

        $this->json([1]);
    }



    public function access_list(Action $view, RequestContext $request)
    {
        $view->setLayout(new AdministrationLayout(), new EmptyLayout());
        $groups = Criteria::query(AccessGroup::getClass())->findAsFieldArray(AccessGroup::F_NAME, true);
        $view->assign("agroups", $groups);

        $view->setLayout(new AdministrationLayout(), new EmptyLayout());





        $ds = TableDataSource::fromClass(AccessPoint::getClass());
        //$ds->setGlobalSearchFields(["point_object_friendly_id"]);


        $list = ColumnList::create()
            ->addColumn(Simple::_new("id", "id"))
            ->addColumn(Simple::_new("point_type", "Typ"))
            ->addColumn(Simple::_new("point_action", "Action"))
            ->addColumn(Simple::_new("point_object_friendly_id", "Friendly id"))
            ->addColumn(Editable::_boolswitch("control_enabled","Control",Router::link("./changePointControl"),["id"]))
            ->addColumn(Template::_new(function($context) use ($groups){
                ?>
                <select multiple="multiple" class="span5 group-select" context="<?=$context["id"]?>"">
                <? foreach($groups as $id=>$name){ ?>
                    <?if( !in_array($id, array(2,4))){ ?>
                        <option <?=$id&$context["groups"]?'selected="selected"':''?> value="<?=$id?>" ><?=$name?></option>
                    <? } ?>
                <? } ?>
                </select>
                <?
            }))
        ;
        $table = new Table("access",$ds,$list);
        $table->prependWidged(FiltersPresenter::create([$table]));
        $l = LayoutBuilder::create();

        $l->insert(Toolbar::_new([
                Breadcrumb::create([
                "System",
                BreadcrumbElement::create("Udzielone dostępy")->setActive(1)
                ])
            ]));

        $table->addDataColumn(["groups"]);
        $l->add($table);


        $view->assign("generator", AdministrationLayout::page(new WidgetsSet([$l])));


    }

    public function access_changePointControl(){
        $obj = AccessPoint::get()->findByKey($this->request["id"]);
        $obj[AccessPoint::F_CONTROL_ENABLED] = $obj[AccessPoint::F_CONTROL_ENABLED]?0:1;
        $obj->save();
        $this->json([1]);
    }

    public function changePointGroup(IAction $action, RequestContext $request)
    {
        //$tmp = explode(",",$request["groups"]);

        $point = Criteria::query(AccessPoint::getClass())->findByKey($request["accessPoint"]);
        if ($request["groups"]) {
            $sum = array_sum($request["groups"]);
            $point["groups"] = $sum;
        } else
            $point["groups"] = 0;


        $point->save();
        $this->json([true]);
    }

    public function auth_loginAs(IAction $action, RequestContext $request)
    {

        $auth = Auth::getDefault();
        if (empty($request["loginToLoginAs"]) && empty($request["id"])) {
            $auth->restoreShadowUser();
        }elseif($request["id"]){
            $user = User::get()->findByKey($request["id"]);
            $auth->doLogin($user["login"], false, true);
        } else
            $auth->doLogin($request["loginToLoginAs"], false, true);

        if(RequestContext::getDefault()->isXHR())
            $this->json([true]);
        else
            $this->back();
    }

    public function dashboard_currentlyLogged(Action $view, RequestContext $request)
    {
        $view->setLayout(new EmptyLayout());

        $span = new \DateTime();
        $span->sub(new \DateInterval("PT30M"));
        $spanFormatted = $span->format("Y-m-d H:i:s");

        $db = Project::getInstance()->getDB();

        $query = "select s.*,u.login from access_sessions s left join access_user u on(s.user_id=u.id) where last>'{$spanFormatted}'  order by last desc";
        $result = Project::getInstance()->getDB()->query($query);
        $view->assign("list", $result);


        $query = "select count(*) from access_sessions s where last>'{$spanFormatted}' and user_id is NULL";
        $result = $db->query($query)->fetchColumn();
        $view->assign("countNotLogged", $result);

        $query = "select count(*) from access_sessions s where last>'{$spanFormatted}' and user_id is not NULL";
        $result = $db->query($query)->fetchColumn();
        $view->assign("countLogged", $result);


    }

    public function getUsers(){
        $q = RequestContext::getDefault()["q"];

        $result = User::get()
            ->setColumns([User::F_LOGIN, User::F_EMAIL])
            ->c(User::F_ACTIVE, 1)
            ->c(User::F_LOGIN, "", Criteria::C_NOT_EQUAL)
            ->limit(0,10)
            ->order(User::F_LOGIN);


        $result->addSearchCondition([User::F_LOGIN, User::F_NAME], "%".$q."%");

        $result = $result->find();

        $data = [];
        foreach($result as $el){
            $data[] =[ "id" => $el["id"], "text" => $el["login"] ];
        }

        $this->json([ "results" => $data, "more"=>false] );
    }


}
