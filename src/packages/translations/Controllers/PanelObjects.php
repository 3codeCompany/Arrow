<?php

namespace Arrow\Translations\Controllers;


use App\Controllers\BaseController;
use App\Models\Persistent\TransactionText;
use Arrow\CMS\Models\Persistent\Page;
use Arrow\Common\Layouts\ReactComponentLayout;
use Arrow\Controls\API\Components\Toolbar;
use Arrow\Controls\API\Forms\Fields\Button;
use Arrow\Controls\API\Forms\Fields\Files;
use Arrow\Controls\API\Forms\Fields\Helpers\BoolSwitch;
use Arrow\Controls\Helpers\FormHelper;
use Arrow\Common\Models\Helpers\TableListORMHelper;
use Arrow\Models\Dispatcher;
use Arrow\Models\Operation;
use Arrow\Models\Project;
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
use Arrow\Shop\Models\Persistent\Category;
use Arrow\Shop\Models\Persistent\Property;
use Arrow\Translations\Models\Language;
use Arrow\Translations\Models\LanguageText;
use Arrow\Translations\Models\ObjectTranslation;
use Arrow\Translations\Models\Translations;
use Arrow\Media\Element;
use Arrow\Media\ElementConnection;
use Arrow\Media\Models\MediaAPI;
use Arrow\Controls\API\Table\Table;
use Arrow\Router;

class PanelObjects extends BaseController
{
    public function index()
    {
        $this->action->setLayout(new ReactComponentLayout());
        //$this->action->assign('fields' , LanguageText::getFields());
        $this->action->assign('language', Language::get()->findAsFieldArray(Language::F_NAME, Language::F_CODE));

        $db = Project::getInstance()->getDB();
        $t = ObjectTranslation::getTable();
        $db->exec("DELETE n1 FROM common_lang_objects_translaction n1, common_lang_objects_translaction n2 WHERE n1.id > n2.id AND n1.source=n2.source and n1.lang=n2.lang and n1.id_object=n2.id_object and n1.field=n2.field and n1.value is not NULL");

        $this->action->assign("objects", FormHelper::assocToOptions([
            Category::getClass() => "Kategorie",
            Property::getClass() => "Cechy",
            Page::getClass() => "Strony",
        ]));

        $db = Project::getInstance()->getDB();
        $t = ObjectTranslation::getTable();
        $db->query("DELETE n1 FROM {$t} n1, {$t} n2 WHERE n1.id > n2.id AND n1.source=n2.source and n1.lang=n2.lang and n1.field=n2.field and n1.id_object=n2.id_object and n1.class=n2.class");


    }

    public function list()
    {

        $helper = new TableListORMHelper();
        $crit = ObjectTranslation::get();
        $model = $helper->getInputData()['additionalConditions']['model'];
        $tmp = explode("\\", $model);
        $class = "%" . end($tmp);
        //$crit->_field("link", Criteria::C_NOT_EQUAL);
        $crit->c(ObjectTranslation::F_CLASS, $class, Criteria::C_LIKE);
        $crit->_join($model, [ObjectTranslation::F_ID_OBJECT => "id"], "E", $model::getMultilangFields());


        $user = Auth::getDefault()->getUser()->_login();
        $tmp = explode("_", $user);
        if(count($tmp) == 2){
            $crit->_lang($tmp[1]);
        }


        $helper->addDefaultOrder(ObjectTranslation::F_LANG);
        $helper->addDefaultOrder(ObjectTranslation::F_ID_OBJECT);
        $helper->addDefaultOrder(ObjectTranslation::F_FIELD);

        /*if ($model == Property::getClass()) {
            $crit->_join(Category::getClass(), ["E:" . Property::F_CATEGORY_ID => "id"], "C", [Category::F_NAME]);
        }*/

        //$helper->setDebug($crit->find()->getQuery());

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
            ->_source("", Criteria::C_NOT_EQUAL)
            ->_lang($data["lang"]);


        $tmp = explode("\\", $model);
        $class = "%" . end($tmp);
        $criteria->c(ObjectTranslation::F_CLASS, $class, Criteria::C_LIKE);
        $criteria->_join($model, [ObjectTranslation::F_ID_OBJECT => "id"], "E", $model::getMultilangFields());

        /*if ($model == Property::getClass()) {
            $criteria->_join(Category::getClass(), ["E:" . Property::F_CATEGORY_ID => "id"], "C", [Category::F_NAME]);
        }*/

        if ($data["onlyEmpty"]) {
            $criteria->_value([null, ""], Criteria::C_IN);
        }


        // Add some data

        $result = $criteria->find()->toArray(DataSet::AS_ARRAY);


        $columns = [
            "id",
            "field",
            "source",
            "value",
        ];

        foreach ($result as $index => $r) {
            //$columns[2] = $r["field"];
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
