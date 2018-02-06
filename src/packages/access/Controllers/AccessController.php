<?php

namespace Arrow\Access\Controllers;


use Arrow\Access\Models\AccessGroup;
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
use Arrow\Models\Action;
use Arrow\Models\IAction;
use Arrow\Models\Operation;
use Arrow\Models\Project;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\Persistent\DataSet;
use Arrow\Package\Application\Language;
use Arrow\RequestContext;
use Arrow\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
    public function login(Request $request)
    {

        $data = [
            "backgroundImage" => ConfigProvider::get("panel")["loginBackground"],
            "from" => $request->get("from"),
        ];

        $layout = new ReactComponentLayout(null, $data);

        $layout->setOnlyBody(true);
        return $layout;

    }

    /**
     * @Route("/loginAction")
     */
    public function loginAction(Request $request)
    {

        $data = $request->get("data");
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

        return [
            "redirectTo" => trim($request->get("from", Router::getDefault()->getBasePath() . "/admin", "/"))
        ];


    }

    /**
     * @param $action
     * @param RequestContext $request
     * @Route("/logout")
     */
    public function logout(Auth $auth)
    {
        $auth->doLogout();
        if (isset($_SERVER['HTTP_REFERER'])) {
            return RedirectResponse::create($_SERVER['HTTP_REFERER']);
        }
        return [true];
    }


    public function users_account(Action $view, RequestContext $request)
    {
        return [];
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

        $data = $request->get("data");

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


    /**
     * @param Request $request
     * @param Auth $auth
     * @return array
     * @throws \Arrow\Exception
     * @throws \Arrow\ORM\Exception
     * @Route("/loginAs/{key}")
     */
    public function loginAs($key, Request $request, Auth $auth)
    {
        if ($key) {
            $auth->restoreShadowUser();
        } else {
            $user = User::get()->findByKey($key);
            $auth->doLogin($user["login"], false, true);
        }
        return [true];

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
