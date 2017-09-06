<?php

namespace Arrow\Translations\Controllers;


use App\Controllers\BaseController;
use App\Models\Persistent\Category;
use App\Models\Persistent\Property;
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
use Arrow\Controls\Helpers\FormHelper;
use Arrow\Controls\Helpers\TableListORMHelper;
use Arrow\Models\Dispatcher;
use Arrow\Models\Operation;
use Arrow\Models\View;
use Arrow\ORM\Persistent\Criteria,
    \Arrow\Access\Models\Auth,
    \Arrow\ViewManager, \Arrow\RequestContext;
use Arrow\Access\Models\AccessGroup;
use Arrow\ORM\Persistent\DataSet;
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
use Arrow\Translations\Models\ObjectTranslation;
use Arrow\Translations\Models\Translations;
use Arrow\Media\Element;
use Arrow\Media\ElementConnection;
use Arrow\Media\MediaAPI;
use Arrow\Controls\API\Table\Table;
use Arrow\Router;

class PanelObjects extends BaseController
{
    public function index()
    {
        $this->action->setLayout(new ReactComponentLayout());
        //$this->action->assign('fields' , LanguageText::getFields());
        $this->action->assign('language', Language::get()->findAsFieldArray(Language::F_NAME, Language::F_CODE));

        $this->action->assign("objects", FormHelper::assocToOptions([
            Category::getClass() => "Kategorie",
            Property::getClass() => "Cechy"
        ]));


    }

    public function list()
    {

        $helper = new TableListORMHelper();
        $crit = ObjectTranslation::get();
        $model = $helper->getInputData()['additionalConditions']['model'];
        $tmp = explode("\\", $model);
        $class = "%" . end($tmp);
        $crit->c(ObjectTranslation::F_CLASS, $class, Criteria::C_LIKE);
        $crit->_join($model, [ObjectTranslation::F_ID_OBJECT => "id"], "E", $model::getMultilangFields());

        if ($model == Property::getClass()) {
            $crit->_join(Category::getClass(), ["E:" . Property::F_CATEGORY_ID => "id"], "C", [Category::F_NAME]);
        }

        //$helper->addDefaultOrder(Language::F_NAME);
        $this->json($helper->getListData($crit));
    }

    public function downloadLangFile()
    {

        $data = json_decode($this->request["payload"], true);
        $model = $data["model"];


        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("AS - CMS");
        $sh = $objPHPExcel->setActiveSheetIndex(0);

        $criteria = ObjectTranslation::get()
            ->_original("", Criteria::C_NOT_EQUAL)
            ->_lang($data["lang"]);


        $tmp = explode("\\", $model);
        $class = "%" . end($tmp);
        $criteria->c(ObjectTranslation::F_CLASS, $class, Criteria::C_LIKE);
        $criteria->_join($model, [ObjectTranslation::F_ID_OBJECT => "id"], "E", $model::getMultilangFields());

        if ($model == Property::getClass()) {
            $criteria->_join(Category::getClass(), ["E:" . Property::F_CATEGORY_ID => "id"], "C", [Category::F_NAME]);
        }

        if ($data["onlyEmpty"]) {
            $criteria->_value([null, ""], Criteria::C_IN);
        }


        // Add some data

        $result = $criteria->find()->toArray(DataSet::AS_ARRAY);


        $columns = [
            "id",
            "field",
            "orginal",
            "value",
            "module",
        ];

        foreach ($result as $index => $r) {
            $columns[2] = $r["field"];
            foreach ($columns as $key => $c) {

                $sh->setCellValueByColumnAndRow($key, $index, $r[$c]);

            }

            //$sh->setCellValueByColumnAndRow($key +1 , $index, Reclaim::re$r[$c]);
        }
        /*foreach ($columns as $key => $c) {
            $sh->getColumnDimensionByColumn($key)->setAutoSize(true);
        }*/


        // Rename worksheet
        $sh->setTitle('Tłumaczenia ');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);
        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="tłumaczenia_' . $data['lang'] . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save("php://output");
        exit;

    }

    public function delete()
    {
        $elements = ObjectTranslation::get()
            ->_id($this->request["keys"], Criteria::C_IN)
            ->find();

        foreach ($elements as $element) {
            $element->delete();
        }

        $this->json([true]);
    }

    public function inlineUpdate()
    {
        $obj = ObjectTranslation::get()
            ->findByKey($this->request["key"]);

        $obj->setValue(LanguageText::F_VALUE, $this->request["newValue"]);
        $obj->save();


        $this->json([1]);
    }

}
