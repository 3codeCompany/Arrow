<?php

namespace Arrow\Translations\Controllers;


use App\Controllers\BaseController;
use Arrow\Access\Models\Auth;
use Arrow\Common\AdministrationLayout;
use Arrow\Common\AdministrationPopupLayout;
use Arrow\Common\BreadcrumbGenerator;
use Arrow\Common\Links;
use Arrow\Common\Models\Helpers\FormHelper;
use Arrow\Common\Models\Helpers\TableListORMHelper;
use Arrow\Common\PopupFormBuilder;
use Arrow\Common\TableDatasource;
use Arrow\Media\Element;
use Arrow\Media\ElementConnection;
use Arrow\Models\Dispatcher;
use Arrow\Models\Operation;
use Arrow\Models\Project;
use Arrow\Models\View;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\Persistent\DataSet;
use Arrow\Shop\Models\Persistent\Category;
use Arrow\Shop\Models\Persistent\Product;
use Arrow\Shop\Models\Persistent\Property;
use Arrow\Translations\Models\Language;
use Arrow\Translations\Models\LanguageText;
use Arrow\Translations\Models\ObjectTranslation;
use Arrow\Translations\Models\Translations;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PanelObjects
 * @package Arrow\Translations\Controllers
 * @Route("/objects")
 */
class PanelObjects extends BaseController
{
    protected $user;
    public $country = "pl";

    public function __construct()
    {
        $this->user = Auth::getDefault()->getUser()->_login();

        $tmp = explode("_", $this->user);

        if (count($tmp) >= 2) {
            $this->country = $tmp[1];
        }

        if (Auth::getDefault()->getUser()->isInGroup("Partnerzy sprzedaży")) {
            $this->country = substr(Auth::getDefault()->getUser()->_login(), -2);
            Translations::setupLang($this->country);
        }
    }

    /**
     * @Route("/index")
     */
    public function index()
    {

        //$this->action->assign('fields' , LanguageText::getFields());


        $db = Project::getInstance()->getDB();
        $t = ObjectTranslation::getTable();
        //$db->exec("DELETE n1 FROM common_lang_objects_translaction n1, common_lang_objects_translaction n2 WHERE n1.id > n2.id AND n1.source=n2.source and n1.lang=n2.lang and n1.id_object=n2.id_object and n1.field=n2.field and n1.value is not NULL");


        $db = Project::getInstance()->getDB();
        $t = ObjectTranslation::getTable();
        $db->query("DELETE n1 FROM {$t} n1, {$t} n2 WHERE n1.id > n2.id  and n1.lang=n2.lang and n1.field=n2.field and n1.id_object=n2.id_object and n1.class=n2.class");


        return [
            'language' => Language::get()->findAsFieldArray(Language::F_NAME, Language::F_CODE),
            "objects" => FormHelper::assocToOptions(array(
                Category::getClass() => Translations::translateText("Kategorie"),
                Property::getClass() => Translations::translateText("Cechy"),
                Product::getClass() => Translations::translateText("Produkty"),
                //\Arrow\Shop\Models\Persistent\Product::getClass() => "Produkty"

            ))
        ];
    }

    /**
     * @Route("/list")
     */
    public function list()
    {

        $helper = new TableListORMHelper();
        $crit = ObjectTranslation::get();
        $model = $helper->getInputData()['additionalConditions']['model'];
        $tmp = explode("\\", $model);
        $class = "%" . end($tmp);
        //$crit->_field("link", Criteria::C_NOT_EQUAL);
        $crit->c(ObjectTranslation::F_CLASS, $class, Criteria::C_LIKE);
        $crit->_join($model, [ObjectTranslation::F_ID_OBJECT => "id"], "E");//$model::getMultilangFields()


        $user = Auth::getDefault()->getUser()->_login();
        $tmp = explode("_", $user);
        if (count($tmp) == 2) {
            if ($tmp[1] == "ua") {
                $crit->_lang(["ua", "ru"], Criteria::C_IN);
            } elseif ($tmp[1] == "by") {
                $crit->_lang(["by", "ru"], Criteria::C_IN);
            } else {
                $crit->_lang($tmp[1]);
            }
        }


        $helper->addDefaultOrder(ObjectTranslation::F_LANG);
        $helper->addDefaultOrder(ObjectTranslation::F_ID_OBJECT);
        $helper->addDefaultOrder(ObjectTranslation::F_FIELD);

        if ($model == Product::getClass()) {
            $helper->addFilter("E:name", function ($nothing, $filter) use ($crit) {
                if (strpos($filter["value"], "-") !== false) {
                    $chunks = explode("-", $filter["value"]);
                    $crit->c("E:group_key", $chunks[0], Criteria::C_LIKE);
                    $crit->c("E:color", $chunks[1], Criteria::C_LIKE);
                }
            });
        }

        /*if ($model == Property::getClass()) {
            $crit->_join(Category::getClass(), ["E:" . Property::F_CATEGORY_ID => "id"], "C", [Category::F_NAME]);
        }*/

        //$helper->setDebug($crit->find()->getQuery());

        //$helper->addDefaultOrder(Language::F_NAME);
        $this->json($helper->getListData($crit));
    }

    /**
     * @Route("/downloadLangFile")
     */
    public function downloadLangFile(Request $request)
    {

        $data = json_decode($request->get("payload"), true);
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

    /**
     * @Route("/uploadFile")
     */
    public function uploadFile(Request $request, Project $project)
    {
        $data = $request->get("data");
        if ($data["language"] == null) {
            return [
                "status" => "fail",
            ];
        } else {

//            print_r($_FILES["data"]["tmp_name"]["files"][0]["nativeObj"]);
//            die();

            /** @var UploadedFile $fileObj */
            $fileObj = $_FILES["data"]["tmp_name"]["files"][0]["nativeObj"];

            $currentDate = date("d-m-Y");
            $currentTime = date("H:i:s");
            $backupName = $currentDate . "_" . $currentTime . "_" . $this->user . "_" . $data["language"] . ".xls";
            $target = "data/translate_object_uploads/" . $backupName;

            //  Read your Excel workbook
            try {
                $inputFileType = \PHPExcel_IOFactory::identify($fileObj);
                $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
                $objPHPExcel = $objReader->load($fileObj);
            } catch (\Exception $e) {
                die('Error loading file "' . pathinfo($fileObj,
                        PATHINFO_BASENAME) . '": ' . $e->getMessage());
            }

            $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, false);

            $t = ObjectTranslation::getTable();
            $db = $project->getDB();


            $stm = $db->prepare("update $t set value=?  where id=? and lang=?");

            foreach ($sheetData as $row) {

                if ($row[0]) {

                    $stm->execute([
                        $row[3],
                        $row[0],
                        $data["language"],
                    ]);
                }
            }

            move_uploaded_file($fileObj, $target);
            return [
                "status" => "done",
            ];
        }
    }

    /**
     * @Route("/langBackUp")
     */
    public function langBackUp(Request $request)
    {

        $data = json_decode($request->get("payload"), true);
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

        $currentDate = date("d-m-Y");
        $currentTime = date("H:i:s");
        $backupName = $currentDate . "_" . $currentTime . "_" . $this->user . "_" . $request->get("lang") . ".xls";
        $target = "data/translate_object_backups/" . $backupName;

        // Rename worksheet
        $sh->setTitle('Tłumaczenia ');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);
        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="tłumaczenia_' . $data['lang'] . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save($target);
        exit;

    }

    /**
     * @Route("/delete")
     */
    public function delete(Request $request)
    {
        $elements = ObjectTranslation::get()
            ->_id($request->get("keys"), Criteria::C_IN)
            ->find();

        foreach ($elements as $element) {
            $element->delete();
        }

        return [true];
    }

    /**
     * @Route("/inlineUpdate")
     */
    public function inlineUpdate(Request $request)
    {
        $obj = ObjectTranslation::get()
            ->findByKey($request->get("key"));

        $obj->setValue(LanguageText::F_VALUE, $request->get("newValue"));
        $obj->save();


        return [1];
    }

    /**
     * @Route("/history")
     */
    public function history()
    {
        $dir = "data/translate_object_uploads";

        $files = scandir($dir);

        $returnData = [];

        foreach ($files as $file) {
            $slicedFile = explode("_", $file);
            if (count($slicedFile) >= 3) {
                $returnData[] = [
                    "full_name" => $file,
                    "language" => explode(".", $slicedFile[3])[0],
                    "user" => $slicedFile[2],
                    "date" => $slicedFile[0],
                    "time" => $slicedFile[1],
                ];
            }
        }

        return [
            "countAll" => count($files) - 2,
            "data" => $returnData,
            "debug" => false,
        ];
    }


    /**
     * @Route("/single-object/{model}/{key}")
     * @return array
     */
    public function singleObject($model, $key)
    {

        $obj = $model::get()->findByKey($key);
        $langFields = $model::getMultiLangFields();


        $data = [];

        foreach ($langFields as $field) {

            $row = [
                "lang" => "Polski",
                "langId" => "-1",
                "langCode" => "Pl",
                "trans" => []
            ];

            foreach ($langFields as $field) {
                $row["trans"][$field] = $obj[$field];
            }

            $data[] = $row;
        }


        $langs = Language::get()
            ->_code("pl", Criteria::C_NOT_EQUAL)
            ->order(Language::F_NAME)
            ->find();

        foreach ($langs as $l) {
            Translations::translateObject($obj, $l->_code());
            $row = [
                "lang" => $l->_name(),
                "langId" => $l->_id(),
                "langCode" => $l->_code(),
                "trans" => []
            ];

            foreach ($langFields as $field) {
                $row["trans"][$field] = $obj[$field];
            }

            $data[] = $row;
        }


        return [
            //"langs" => $langs,
            "data" => $data,
            "model" => $model,
            "objectKey" => $key
        ];
    }

    /**
     * @Route("/single-object-save/{model}/{key}")
     * @return array
     */
    public function singleObjectSave($model, $key, Request $request)
    {
        $obj = $model::get()->findByKey($key);

        $data = $request->get("data");
        foreach ($data as $lang => $entries) {
            Translations::saveObjectTranslation($obj, $entries, $lang);
        }

        return [true];
    }
}
