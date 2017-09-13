<?php

namespace Arrow\Translations\Controllers;


use App\Controllers\BaseController;
use App\Models\Persistent\TransactionText;
use Arrow\Common\Layouts\ReactComponentLayout;
use Arrow\Controls\API\Components\Toolbar;
use Arrow\Controls\API\Forms\Fields\Button;
use Arrow\Controls\API\Forms\Fields\File;
use Arrow\Controls\API\Forms\Fields\Files;
use Arrow\Controls\API\Forms\Fields\Helpers\BoolSwitch;
use Arrow\Controls\API\Forms\Fields\Date;
use Arrow\Controls\API\Forms\Fields\Hidden;
use Arrow\Controls\API\Forms\Fields\Select;
use Arrow\Controls\API\Forms\Fields\SwitchF;
use Arrow\Controls\API\Forms\Fields\Text;
use Arrow\Controls\API\Forms\Fields\Textarea;
use Arrow\Controls\API\Forms\Fields\Wyswig;
use Arrow\Controls\API\Forms\FieldsList;
use Arrow\Controls\API\Forms\Form;
use Arrow\Controls\API\Forms\FormBuilder;
use Arrow\Controls\API\Table\ColumnList;
use Arrow\Controls\API\Table\Columns\Menu;
use Arrow\Controls\Helpers\TableListORMHelper;
use Arrow\Models\Dispatcher;
use Arrow\Models\Operation;
use Arrow\Models\View;
use Arrow\ORM\Persistent\Criteria,
    \Arrow\Access\Models\Auth,
    \Arrow\ViewManager, \Arrow\RequestContext;
use Arrow\Access\Models\AccessGroup;
use Arrow\Package\Application\PresentationLayout;
use Arrow\Controls\API\Forms\BuilderSchemas\Bootstrap;
use Arrow\Common\AdministrationLayout;
use Arrow\Common\AdministrationPopupLayout;
use Arrow\Common\BreadcrumbGenerator;
use Arrow\Common\Layouts\EmptyLayout;
use Arrow\Common\Links;
use Arrow\Common\PopupFormBuilder;
use Arrow\Common\TableDatasource;
use Arrow\Translations\Models\Language;
use Arrow\Translations\Models\LanguageText;
use Arrow\Translations\Models\Translations;
use Arrow\Media\Element;
use Arrow\Media\ElementConnection;
use Arrow\Media\MediaAPI;
use Arrow\Controls\API\Table\Table;
use Arrow\Router;

class PanelStatic extends BaseController
{
    public function index()
    {
        $this->action->setLayout(new ReactComponentLayout());
        $this->action->assign('language', Language::get()->findAsFieldArray(Language::F_NAME, Language::F_CODE));
    }

    public function list()
    {
        $c = LanguageText::get();
        $helper = new TableListORMHelper();

        $search = $helper->getInputData()["additionalConditions"]["search"];
        if ($search) {
            $c->addSearchCondition([LanguageText::F_ORIGINAL], "%{$search}%", Criteria::C_LIKE);
        }
        //$helper->addDefaultOrder(Language::F_NAME);
        $this->json($helper->getListData($c));
    }


    public function delete()
    {
        $elements = LanguageText::get()
            ->_id($this->request["keys"], Criteria::C_IN)
            ->find();

        foreach ($elements as $element) {
            $element->delete();
        }

        $this->json([true]);
    }

    public function inlineUpdate()
    {
        $obj = LanguageText::get()
            ->findByKey($this->request["key"]);

        $obj->setValue(LanguageText::F_VALUE, $this->request["newValue"]);
        $obj->save();


        $this->json([1]);
    }


}
