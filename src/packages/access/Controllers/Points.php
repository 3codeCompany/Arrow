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
use Arrow\Models\AnnotationRouteManager;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AccessRights
 * @package Arrow\Access\Controllers
 * @Route("/points")
 */
class Points extends \Arrow\Models\Controller
{

    /**
     * @Route("/list")
     */
    public function list()
    {
        $groups = AccessGroup::get()
            ->_id(4, Criteria::C_GREATER_THAN)
            ->findAsFieldArray(AccessGroup::F_NAME, true);
        return ["agroups" => $groups];

    }


    /**
     * @Route("/getData")
     */
    public function getData(Request $request)
    {
        $criteria = AccessPoint::get();
        $helper = new TableListORMHelper();
        //$helper->setDebug(true);

        $helper->addFilter("groups", function ($c, $filter) use ($criteria, $request) {

            $criteria->_groups(array_sum($filter["value"]), Criteria::C_BIT_AND);

        });
        $helper->addFilter("existsInRoute", function ($c, $filter) use ($criteria, $request) {

            $annotatonRouteManager = new AnnotationRouteManager($request);
            $routing = $annotatonRouteManager->exposeRouting();
            $tmp = [];
            foreach ($routing as $route) {
                $tmp[] = $route["_routePath"];
            }

            if ($filter["value"] == "exists") {
                $criteria->_pointObjectFriendlyId($tmp, Criteria::C_IN);
            } else {
                $criteria->_pointObjectFriendlyId($tmp, Criteria::C_NOT_IN);
            }
        });


        $this->json($helper->getListData($criteria));
    }

    /**
     * @Route("/sync-access-points")
     */
    public function syncAccessPoints(Request $request)
    {
        $annotatonRouteManager = new AnnotationRouteManager($request);
        $routing = $annotatonRouteManager->exposeRouting();

        foreach ($routing as $route) {
            AccessAPI::checkAccess("view", "show", $route["_routePath"], "");
        }

        return [];
    }

    /**
     * @Route("/delete")
     */
    public function delete(Request $request)
    {
        AccessPoint::get()
            ->_id($request->get("keys"), Criteria::C_IN)
            ->find()
            ->delete();

        $this->json([1]);
    }

    /**
     * @Route("/save")
     */
    public function save(Request $request)
    {
        AccessPoint::get()
            ->findByKey($request->get("key"))
            ->setValues($request->get("data"))
            ->save();
        $this->json([1]);
    }


    /**
     * @Route("/changePointControl")
     */
    public function changePointControl(Request $request)
    {
        $obj = AccessPoint::get()->findByKey($request->get("id"));
        $obj[AccessPoint::F_CONTROL_ENABLED] = $obj[AccessPoint::F_CONTROL_ENABLED] ? 0 : 1;
        $obj->save();
        return [1];
    }

    /**
     * @Route("/changePointGroup")
     */
    public function changePointGroup(Request $request)
    {
        //$tmp = explode(",",$request["groups"]);

        $point = Criteria::query(AccessPoint::getClass())->findByKey(
            $request->get("accessPoint")
        );
        if ($request["groups"]) {
            $sum = array_sum($request["groups"]);
            $point["groups"] = $sum;
        } else {
            $point["groups"] = 0;
        }
        $point->save();
        return [true];
    }

    /**
     * @Route("/getData")
     */
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


}
