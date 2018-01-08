<?php

namespace Arrow\Access\Controllers;


use function array_reduce;
use Arrow\Access\Models\AccessAPI;
use Arrow\Access\Models\AccessGroup;
use Arrow\Access\Models\AccessPoint;
use Arrow\Access\Models\AccessUserGroup;
use Arrow\Access\Models\Auth;
use Arrow\Access\Models\User;
use Arrow\Common\Layouts\ReactComponentLayout;
use Arrow\Common\Models\History\History;
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
use Arrow\Common\Models\Helpers\Validator;
use Arrow\Controls\api\Layout\LayoutBuilder;
use Arrow\Controls\api\SerenityJS;
use Arrow\Controls\API\Table\ColumnList;
use Arrow\Controls\API\Table\Columns\Editable;
use Arrow\Controls\API\Table\Columns\Simple;
use Arrow\Controls\API\Table\Columns\Template;
use Arrow\Controls\api\WidgetsSet;
use Arrow\Common\Models\Helpers\TableListORMHelper;
use Arrow\Models\IAction;
use Arrow\Models\Project;
use Arrow\ORM\Persistent\DataSet;
use Arrow\Package\Application\Language;
use Arrow\Common\AdministrationLayout;
use Arrow\Common\Layouts\EmptyLayout;
use Arrow\Common\Models\Wigets\Table\TableDataSource;
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
class AccessRights extends \Arrow\Models\Controller
{

    public function __construct()
    {
        //AccessAPI::checkInstallation();
    }


    public function getData()
    {
        $helper = new TableListORMHelper();
        $criteria = AccessPoint::get();
        $this->json($helper->getListData($criteria));
    }

    public function save()
    {
        AccessPoint::get()
            ->findByKey($this->request["key"])
            ->setValues($this->request["data"])
            ->save();
        $this->json([1]);
    }

    public function list(Action $view, RequestContext $request)
    {

        $this->action->setLayout(new ReactComponentLayout());
        $groups = AccessGroup::get()
            ->_id(4, Criteria::C_GREATER_THAN)
            ->findAsFieldArray(AccessGroup::F_NAME, true);
        $view->assign("agroups", $groups);

        return;


    }

    public function access_changePointControl()
    {
        $obj = AccessPoint::get()->findByKey($this->request["id"]);
        $obj[AccessPoint::F_CONTROL_ENABLED] = $obj[AccessPoint::F_CONTROL_ENABLED] ? 0 : 1;
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
        } else {
            $point["groups"] = 0;
        }


        $point->save();
        $this->json([true]);
    }

    public function auth_loginAs(IAction $action, RequestContext $request)
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


}
