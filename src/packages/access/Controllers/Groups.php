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
class Groups extends \Arrow\Models\Controller
{




    public function getData()
    {
        $helper = new TableListORMHelper();
        $this->json($helper->getListData(AccessGroup::get()->_id(4, Criteria::C_GREATER_THAN)));
    }


    public function list()
    {
        $this->action->setLayout(new ReactComponentLayout());

    }

    public function delete($view, RequestContext $request)
    {
        AccessGroup::get()->findByKey($request["key"])->delete();

        $this->json([1]);
    }

    public function edit(Action $view, RequestContext $request)
    {

        $this->action->setLayout(new ReactComponentLayout());

        $group = AccessGroup::get()->findByKey($request['key']);
        $this->action->assign("group", $group);


    }

    public function save($view, RequestContext $request)
    {
        if ($request["key"]) {
            AccessGroup::get()->findByKey($request["key"])->setValues($request["data"])->save();
        } else {
            AccessGroup::create($request["data"]);
        }

        $this->json([1]);
    }

    public function access_getData()
    {
        $helper = new TableListORMHelper();
        $criteria = AccessPoint::get();
        $this->json($helper->getListData($criteria));
    }

    public function access_save()
    {
        AccessPoint::get()
            ->findByKey($this->request["key"])
            ->setValues($this->request["data"])
            ->save();
        $this->json([1]);
    }


}
