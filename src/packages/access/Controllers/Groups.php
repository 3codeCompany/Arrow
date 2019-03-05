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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/groups")
 */
class Groups extends \Arrow\Models\Controller
{


    /**
     * @Route("/getData")
     */
    public function getData()
    {
        $helper = new TableListORMHelper();
        $this->json($helper->getListData(AccessGroup::get()->_id(4, Criteria::C_GREATER_THAN)));
    }

    /**
     * @Route("/list")
     */
    public function list()
    {
        return [];
    }

    /**
     * @Route("/delete")
     */
    public function delete(Request $request)
    {
        AccessGroup::get()->findByKey($request->get("key"))->delete();

        return [];
    }

    /**
     * @Route("/edit")
     */
    public function edit(Request $request)
    {
        $group = AccessGroup::get()->findByKey($request->get('key'));

        return [
            "group" => $group
        ];

    }

    /**
     * @Route("/save")
     */
    public function save(Request $request)
    {
        if ($request->get("key")) {
            AccessGroup::get()->findByKey($request->get("key"))->setValues($request->get("data"))->save();
        } else {
            AccessGroup::create($request->get("data"));
        }

        return [1];
    }


    /**
     * @Route("/widget")
     * @Route("/widget/{mask}/{owner}")
     */
    public function widget($mask = 0, $owner = 0)
    {

        $groups = AccessGroup::get()
            ->_id(4, Criteria::C_GREATER_THAN)
            ->findAsFieldArray(AccessGroup::F_NAME, true);

        $owners = User::findByGroupId([64, 16, 1024, 65536])
            ->toPureArray();


        foreach($owners as &$user){
            $user = [
                "id" => $user["id"],
                "login" => $user["login"],
            ];
        }

        return [
            "agroups" => $groups,
            "mask" => $mask,
            "owner" => $owner,
            "owners" => $owners
        ];
    }


}
