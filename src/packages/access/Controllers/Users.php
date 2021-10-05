<?php

namespace Arrow\Access\Controllers;


use Arrow\Access\Models\AccessGroup;
use Arrow\Access\Models\AccessUserGroup;
use Arrow\Access\Models\Auth;
use Arrow\Access\Models\User;
use Arrow\Common\AdministrationLayout;
use Arrow\Common\Models\Helpers\Validator;
use Arrow\Common\Models\History\History;
use Arrow\Common\Models\Wigets\Table\TableDataSource;
use Arrow\Common\Track;
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
use Arrow\Models\Operation;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\Persistent\DataSet;
use Arrow\Package\Application\Language;
use Arrow\RequestContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use function array_reduce;
use function strlen;

/**
 * Class Users
 * @package Arrow\Access\Controllers
 * @Route("/users")
 */
class Users extends \Arrow\Models\Controller
{


    public function account()
    {

        /** @var User $user */
        $user = Auth::getDefault()->getUser();

        $this->json([
            "user" => [
                "login" => $user["login"],
                "email" => $user["email"]
            ]
        ]);
    }

    public function saveAccount(Request $request)
    {

        $user = Auth::getDefault()->getUser();

        $d = $request->get("data");
        $validator = Validator::create($d)
            ->required(["email"])
            ->email(["email"]);


        if ($d["password_new"]) {
            $validator->required(["password_old", "password_new", "password_confirm"]);
            if ($d["password_new"] != $d["password_confirm"]) {
                $validator->addFieldError("password_new", "Nowe hasło nie jest zgodne z potwierdzeniem");

            }
            if (strlen($d["password_new"]) < 6) {
                $validator->addFieldError("password_new", "Nowe hasło powinno mieć co najmniej 6 znaków");
            }

            if (User::generatePassword($d["password_new"]) != $user->_password()) {
                $validator->addFieldError("password_old", "Podane hasło jest błędne");
            }
        }

        if (!$validator->check()) {
            $this->json($validator->response());
        }

        $user = Auth::getDefault()->getUser();
        $user->setValues([
            User::F_EMAIL => $d["email"],
            User::F_PASSWORD => $d["password_new"],
            User::F_PASSWORD_CHANGED => date("Y-m-d H:i:s")
        ]);
        $user->save();
        $this->json([1]);
    }


    /**
     * @Route("/getData")
     */
    public function getData()
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
            if (count($groups) > 1) {
                $criteria->addGroupBy("id");
            }
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

    /**
     * @Route("/list")
     */
    public function list()
    {
        return [
            "accessGroups" => AccessGroup::get()->findAsFieldArray(AccessGroup::F_NAME, true)
        ];
    }

    /**
     * @Route("/delete")
     */
    public function delete(Request $request)
    {
        $user = User::get()->findByKey($request->get("key"));

        if (strpos($user[User::F_LOGIN], "_deleted_user") === false) {
            $user[User::F_LOGIN] = $user[User::F_LOGIN] . "_deleted_user";
            $user[User::F_PASSWORD] = "deleted$%^@deleted()";
            $user->save();
        }

        $this->json([1]);
    }

    /**
     * @Route("/edit")
     * @Route("/edit/{key}")
     */
    public function edit(Request $request, $key = false)
    {

        $user = User::get()->findByKey($request->get("key", $key));


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
        $data = [];
        $data["history"] = $history;
        $data["groups"] = $groups;
        $data["user"] = $user;
        $data["selectedGroups"] = $selectedGroups;

        return $data;


    }

    /**
     * @Route("/save")
     */
    public function save(Request $request)
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

        $this->json(["key" => $user->_id()]);
    }


}
