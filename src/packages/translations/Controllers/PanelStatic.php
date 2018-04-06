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
use Arrow\Translations\Models\Language;
use Arrow\Translations\Models\LanguageText;
use Arrow\Translations\Models\Translations;
use Arrow\Media\Element;
use Arrow\Media\ElementConnection;
use Arrow\Media\MediaAPI;
use Arrow\Controls\API\Table\Table;
use Arrow\Router;
use function file_get_contents;

class PanelStatic extends BaseController
{
    public function index()
    {
        $this->action->setLayout(new ReactComponentLayout());
        $this->action->assign('language', Language::get()->findAsFieldArray(Language::F_NAME, Language::F_CODE));
        $db = Project::getInstance()->getDB();
        $t = LanguageText::getTable();
        $db->query("DELETE n1 FROM {$t} n1, {$t} n2 WHERE n1.id > n2.id AND n1.hash=n2.hash and n1.lang=n2.lang");
    }

    public function list()
    {
        $c = LanguageText::get();
        $helper = new TableListORMHelper();

        $user = Auth::getDefault()->getUser()->_login();
        $tmp = explode("_", $user);
        if(count($tmp) == 2){
            $c->_lang($tmp[1]);
        }


        $search = $helper->getInputData()["additionalConditions"]["search"];
        if ($search) {
            $c->addSearchCondition([LanguageText::F_ORIGINAL], "%{$search}%", Criteria::C_LIKE);
        }
        $data = $helper->getListData($c);

        //$data["debug"] = $c->find()->getQuery();
        //$helper->addDefaultOrder(Language::F_NAME);
        $this->json($data);
    }

    public function uploadLangFile()
    {


        $file = $_FILES["file"]["tmp_name"][0];

        //  Read your Excel workbook
        try {
            $inputFileType = \PHPExcel_IOFactory::identify($file);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($file);
        } catch (\Exception $e) {
            die('Error loading file "' . pathinfo($file, PATHINFO_BASENAME) . '": ' . $e->getMessage());
        }
        $sheet = $objPHPExcel->getSheet(0);

        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, false);


        $t = $t = LanguageText::getTable();
        $db = Project::getInstance()->getDB();

        $stm = $db->prepare("update $t set value=? where id=?");
        $db->beginTransaction();

        foreach ($sheetData as $row) {
            if($row[0]) {
                //print $row[0]." : ".$row[2]."<br />";
                $stm->execute([
                    $row[2],
                    $row[0],

                ]);
            }
        }
        $db->commit();




        $this->json();
    }

    public function downloadLangFile()
    {

        $data = json_decode($this->request["payload"], true);


        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("CMS");
        $sh = $objPHPExcel->setActiveSheetIndex(0);

        $criteria = LanguageText::get()
            ->_lang($data["lang"]);

        if ($data["onlyEmpty"]) {
            $criteria->_value([null, ""], Criteria::C_IN);
        }
        // Add some data

        $result = $criteria->find()->toArray(DataSet::AS_ARRAY);

        $columns = [
            LanguageText::F_ID,
            LanguageText::F_ORIGINAL,
            LanguageText::F_VALUE,
            LanguageText::F_MODULE,
        ];


        $sh->setCellValueByColumnAndRow(0, 1, "id");
        $sh->setCellValueByColumnAndRow(1, 1, "Orginał");
        $sh->setCellValueByColumnAndRow(2, 1, "Tłumaczenie");
        $sh->setCellValueByColumnAndRow(3, 1, "Moduł");
        foreach ($result as $index => $r) {
            foreach ($columns as $key => $c) {
                $sh->setCellValueByColumnAndRow($key, $index + 2, $r[$c]);
            }

            //$sh->setCellValueByColumnAndRow($key +1 , $index, Reclaim::re$r[$c]);
        }
        $objPHPExcel->getActiveSheet()->getStyle('B1:D5000')
            ->getAlignment()->setWrapText(true);

        foreach ($columns as $key => $c) {
            //$sh->getColumnDimensionByColumn($key)->setAutoSize(true);
            //$sh->getColumnDimensionByColumn($key)->setWidth(200);
        }

        $sh->getColumnDimensionByColumn(1)->setWidth(70);
        $sh->getColumnDimensionByColumn(2)->setWidth(70);


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

