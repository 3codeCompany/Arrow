<?php

namespace Arrow\Access\Controllers;


use Arrow\Access\Models\AccessGroup;
use Arrow\Access\Models\AccessPoint;
use Arrow\Access\Models\AccessUserGroup;
use Arrow\Access\Models\Auth;
use Arrow\Access\Models\User;
use Arrow\Common\AdministrationLayout;
use Arrow\Common\Layouts\EmptyLayout;
use Arrow\Common\Layouts\ReactComponentLayout;
use Arrow\Common\Models\Helpers\Validator;
use Arrow\Common\Models\History\History;
use Arrow\Common\Models\Wigets\Table\TableDataSource;
use Arrow\Common\Track;
use Arrow\ConfigProvider;
use Arrow\Controls\api\common\AjaxLink;
use Arrow\Controls\api\common\BreadcrumbElement;
use Arrow\Controls\api\common\ContextMenu;
use Arrow\Controls\api\common\Icons;
use Arrow\Controls\api\common\Link;
use Arrow\Controls\API\Components\Breadcrumb;
use Arrow\Controls\API\Components\MultiFile;
use Arrow\Controls\API\Components\Toolbar;
use Arrow\Controls\API\FilterPanel;
use Arrow\Controls\api\Filters\SelectFilter;
use Arrow\Controls\API\FiltersPresenter;
use Arrow\Controls\API\Forms\Fields\Hidden;
use Arrow\Controls\API\Forms\Fields\Select;
use Arrow\Controls\API\Forms\Fields\SwitchF;
use Arrow\Controls\API\Forms\Fields\Text;
use Arrow\Controls\API\Forms\Fields\Textarea;
use Arrow\Controls\API\Forms\Form;
use Arrow\Controls\api\Layout\LayoutBuilder;
use Arrow\Controls\api\SerenityJS;
use Arrow\Controls\API\Table\ColumnList;
use Arrow\Controls\API\Table\Columns\Editable;
use Arrow\Controls\API\Table\Columns\Simple;
use Arrow\Controls\API\Table\Columns\Template;
use Arrow\Controls\API\Table\Table;
use Arrow\Controls\api\WidgetsSet;
use Arrow\Models\Action;
use Arrow\Models\IAction;
use Arrow\Models\Operation;
use Arrow\Models\Project;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\Persistent\DataSet;
use Arrow\Package\Application\Language;
use Arrow\RequestContext;
use Arrow\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use function array_reduce;


class AccessController extends \Arrow\Models\Controller
{


    /**
     * @param Action $view
     * @param RequestContext $request
     * @Route("/login")
     */
    public function login()
    {
        $request = Request::createFromGlobals();

        $data = [
            "applicationTitle" => ConfigProvider::get("panel")["title"],
            "backgroundImage" => ConfigProvider::get("panel")["loginBackground"],
            "appPath" => RequestContext::getBaseUrl(),
            "from" => $request->get("from"),
        ];

        return new EmptyLayout(null, $data);

    }

    /**
     * @Route("/loginAction")
     */
    public function loginAction()
    {

        $data = $this->request->get("data");
        $validator = Validator::create($data)
            ->required(["login", "password"]);

        if (!$validator->check()) {
            $this->json($validator->response());
        }


        $authHandler = Auth::getDefault();
        $authHandler->doLogout();
        $res = $authHandler->doLogin($data["login"], $data["password"]);


        if (!$authHandler->isLogged()) {
            $validator->addError("Nieprawidłowy login lub hasło");
            $this->json($validator->response());
        }

        $this->json(["redirectTo" => trim($this->request->get("from", Router::getDefault()->getBasePath() . "/admin", "/"))]);


    }

    public function logout($action, RequestContext $request)
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
            ->panel("Twoje konto", Icons::USER);


        $form = Form::_new("editAccount", $user)
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
            ->formField("Email", Text::_new(User::F_EMAIL, "Email"), [LayoutBuilder::CONF_COLUMNS => 4])
            //->formField("Tel. stacjonarny", Text::_new(User::F_EMAIL, "Tel. stacjonarny"),[LayoutBuilder::CONF_COLUMNS => 3])
            //->formField("Tel. komórkowy", Text::_new(User::F_EMAIL, "Tel. komórkowy"),[LayoutBuilder::CONF_COLUMNS => 3])

            ->formField("Zmień hasło", Text::_new(User::F_PASSWORD, "Zostaw puste jeśli nie zmieniasz")->setValue(""), [LayoutBuilder::CONF_COLUMNS => 4])
            ->row()
            ->label("Avatar")
            ->col2(MultiFile::_new("photo", $user))
            ->rowEnd()
            ->add($form->getSubmit(), ["offset" => 2])
            ->separator()
            ->formEnd()
            ->tabEnd()
            //->tab("Ostatnie logowania")
            ->tabSetEnd();


        $view->setGenerator(AdministrationLayout::page($l));
    }

    public function users_accountSave($op, RequestContext $request)
    {
        $user = Auth::getDefault()->getUser();
        $user->setValues($request["data"]);
        $user->save();
        $this->json();
    }

    public function auth_change_password($action, RequestContext $request)
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

    public function users_getData()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        //group filtering
        $groups = false;
        if (isset($data["filters"]["group"])) {
            $groups = $data["filters"]["group"]["value"];
            unset($data["filters"]["group"]);
        }

        $criteria = TableDataSource::prepareCriteria(User::get(), $data);

        if ($groups) {
            $criteria->_join(AccessUserGroup::getClass(), ["id" => AccessUserGroup::F_USER_ID], "AG", ["id"]);
            $criteria->c("AG:" . AccessUserGroup::F_GROUP_ID, $groups, Criteria::C_IN);
        }

        $response = TableDataSource::prepareResponse($criteria, $data);

        //adding access group information
        $usersId = array_reduce($response["data"], function ($p, $c) {
            $p[] = $c["id"];
            return $p;
        }, []);
        $groups = AccessGroup::get()
            ->setColumns([AccessGroup::F_NAME])
            ->_join(AccessUserGroup::getClass(), ["id" => AccessUserGroup::F_GROUP_ID], "UG", [AccessUserGroup::F_USER_ID])
            ->c("UG:" . AccessUserGroup::F_USER_ID, $usersId, Criteria::C_IN)
            ->find()->toArray(DataSet::AS_ARRAY);

        foreach ($response["data"] as &$user) {
            $user["groups"] = [];
            foreach ($groups as $row) {
                if ($row["UG:user_id"] == $user["id"]) {
                    $user["groups"][] = $row["name"];
                }
            }

        }

        $response["debug"] = false;
        $this->json($response);
    }

    public function users_list(Action $view, RequestContext $request)
    {
        $this->action->setLayout(new ReactComponentLayout());
        $this->action->assign("accessGroups", AccessGroup::get()->findAsFieldArray(AccessGroup::F_NAME, true));

    }

    public function users_delete($view, RequestContext $request)
    {
        $user = User::get()->findByKey($request["key"]);
        $user->delete();
        $this->json([1]);
    }

    public function users_edit(Action $view, RequestContext $request)
    {
        $view->setLayout(new ReactComponentLayout());
        $user = User::get()->findByKey($request["key"]);


        $groups = Criteria::query(AccessGroup::getClass())->findAsFieldArray('name', true);
        $selectedGroups = [];
        $history = false;
        if ($user) {
            $selectedGroups = AccessUserGroup::get()
                ->c("user_id", $user->getPKey())
                ->findAsFieldArray('group_id');


            $history = History::getObjectHistoryCriteria($user)
                ->order("id", "desc")
                ->limit(0, 10)
                ->find();
        }
        $this->action->assign("history", $history);
        $this->action->assign("groups", $groups);
        $this->action->assign("user", $user);
        $this->action->assign("selectedGroups", $selectedGroups);


        return;

    }

    public function users_save()
    {

        $data = $this->request["data"];

        $validator = Validator::create($data)
            ->required(["login", "email", "active"])
            ->email("email");


        if (!isset($data["id"])) {
            $validator->required(["password"]);
        }

        if (!$validator->check()) {
            $this->json($validator->response());
        }

        $accessGroups = isset($data["selectedGroups"]) ? $data["selectedGroups"] : [];
        unset($data["selectedGroups"]);

        if (isset($data["id"])) {
            $user = User::get()
                ->findByKey($data["id"]);
            $user
                ->setValues($data)
                ->save();

        } else {
            $user = User::create($data);


        }
        $user->setGroups($accessGroups);

        $this->json([1]);
    }


    public function access_list(Action $view, RequestContext $request)
    {

        $this->action->setLayout(new ReactComponentLayout());
        $groups = AccessGroup::get()
            ->_id(4, Criteria::C_GREATER_THAN)
            ->findAsFieldArray(AccessGroup::F_NAME, true);
        $view->assign("agroups", $groups);

        return;

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
            ->addColumn(Editable::_boolswitch("control_enabled", "Control", Router::link("./changePointControl"), ["id"]))
            ->addColumn(Template::_new(function ($context) use ($groups) {
                print '<select multiple="multiple" class="span5 group-select" context="' . $context["id"] . ' >';
                foreach ($groups as $id => $name) {
                    if (!in_array($id, array(2, 4))) {
                        print  '<option' . ($id & $context["groups"] ? 'selected="selected"' : '') . ' value="' . $id . '">' . $name . '</option>';
                    }
                }
                print  "</select>";

            }));
        $table = new Table("access", $ds, $list);
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

    public function access_changePointControl()
    {
        $obj = AccessPoint::get()->findByKey($this->request["id"]);
        $obj[AccessPoint::F_CONTROL_ENABLED] = $obj[AccessPoint::F_CONTROL_ENABLED] ? 0 : 1;
        $obj->save();
        $this->json([1]);
    }

    public function changePointGroup($action, RequestContext $request)
    {
        //$tmp = explode(",",$request["groups"]);

        $point = Criteria::query(AccessPoint::getClass())->findByKey($request["accessPoint"]);
        if ($request["groups"]) {
            $sum = array_sum($request["groups"]);
            $point["groups"] = $sum;
        } else {
            $point["groups"] = 0;
        }


        $point->save();
        $this->json([true]);
    }

    public function auth_loginAs($action, RequestContext $request)
    {

        $auth = Auth::getDefault();
        if (empty($request["loginToLoginAs"]) && empty($request["id"])) {
            $auth->restoreShadowUser();
        } elseif ($request["id"]) {
            $user = User::get()->findByKey($request["id"]);
            $auth->doLogin($user["login"], false, true);
        } else {
            $auth->doLogin($request["loginToLoginAs"], false, true);
        }

        if (RequestContext::getDefault()->isXHR()) {
            $this->json([true]);
        } else {
            $this->back();
        }
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

    public function getUsers()
    {
        $q = RequestContext::getDefault()["q"];

        $result = User::get()
            ->setColumns([User::F_LOGIN, User::F_EMAIL])
            ->c(User::F_ACTIVE, 1)
            ->c(User::F_LOGIN, "", Criteria::C_NOT_EQUAL)
            ->limit(0, 10)
            ->order(User::F_LOGIN);


        $result->addSearchCondition([User::F_LOGIN, User::F_NAME], "%" . $q . "%");

        $result = $result->find();

        $data = [];
        foreach ($result as $el) {
            $data[] = ["id" => $el["id"], "text" => $el["login"]];
        }

        $this->json(["results" => $data, "more" => false]);
    }


}
