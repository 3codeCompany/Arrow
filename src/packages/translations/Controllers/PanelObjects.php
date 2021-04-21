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
    protected $isTranslator;
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

        if (Auth::getDefault()->getUser()->isInGroup("Tłumacz")) {
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

        if (strpos($this->user, "translator") !== false) {
            $this->isTranslator = true;
        }

        return [
            'language' => Language::get()->findAsFieldArray(Language::F_NAME, Language::F_CODE),
            "objects" => FormHelper::assocToOptions(array(
                Category::getClass() => Translations::translateText("Kategorie"),
                Property::getClass() => Translations::translateText("Cechy"),
                Product::getClass() => Translations::translateText("Produkty"),
                //\Arrow\Shop\Models\Persistent\Product::getClass() => "Produkty"

            )),
            $this->isTranslator && "user" => $this->user,
        ];
    }

    /**
     * @Route("/findKey")
     */
    public function findKey(Request $request) {
        print_r("there");

        return true;
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
            } elseif ($tmp[1] == "lv") {
                $crit->_lang(["lv", "ee", "lt"], Criteria::C_IN);
            } elseif ($tmp[0] == "translator" & $tmp[1] == "ru") {
                $crit->_lang(["lt", "lv", "ee", "ru"], Criteria::C_IN);
            } else {
                $crit->_lang($tmp[1]);
            }
        }

        $helper->addDefaultOrder(ObjectTranslation::F_LANG);
        $helper->addDefaultOrder(ObjectTranslation::F_ID_OBJECT);
        $helper->addDefaultOrder(ObjectTranslation::F_FIELD);

        if($model == Product::getClass()) {
            $helper->addFilter("E:name", function ($nothing, $filter) use ($crit) {
                if (strpos($filter["value"], "-") !== false) {
                    $chunks = explode("-", $filter["value"]);
                    $crit->c("E:group_key", $chunks[0], Criteria::C_LIKE);
                    $crit->c("E:color", $chunks[1], Criteria::C_LIKE);
                }
            });
        }


            $r = $helper->getListData($crit);

            $resultSourceKeys = [];
            foreach ($r["data"] as $item) {
                if (isset($item["source"])) {
                    $val = strlen($item["source"]) > 0 ? $item["source"] : "-empty-";
                    $resultSourceKeys[$val] = $val;
                }
            }
            $trans = ObjectTranslation::get()
                ->_source($resultSourceKeys, Criteria::C_IN)
                ->_lang("ru")
                ->find()
                ->toPureArray()
            ;
            foreach ($trans as $translated) {
                $resultSourceKeys[$translated["source"]] = $translated["value"];
            }
            foreach ($r["data"] as $key => $result) {
                if (isset($result["source"])) {
                    if (isset($resultSourceKeys[$result["source"]])) {
                        $r["data"][$key]["translated_original"] = $resultSourceKeys[$result["source"]];
                    }
                }
            }



        /*if ($model == Property::getClass()) {
            $crit->_join(Category::getClass(), ["E:" . Property::F_CATEGORY_ID => "id"], "C", [Category::F_NAME]);
        }*/

        //$helper->setDebug($crit->find()->getQuery());

        //$helper->addDefaultOrder(Language::F_NAME);
        $this->json($r);
    }


    /**
     * @Route("/downloadLangFile")
     */
    public function downloadLangFile(Request $request)
    {

        $data = json_decode($request->get("payload"), true);
        $model = $data["model"];
        //$model = $helper->getInputData()['additionalConditions']['model']; //todo check

        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("AS - CMS");
        $sh = $objPHPExcel->setActiveSheetIndex(0);

        $criteria = ObjectTranslation::get()
            ->_source("", Criteria::C_NOT_EQUAL)
            ->_lang($data["lang"]);


        $tmp = explode("\\", $model);
        $class = "%" . end($tmp);
        $criteria->c(ObjectTranslation::F_CLASS, $class, Criteria::C_LIKE);
        $columnsToJoin = $model::getMultilangFields();
        if ($model == Product::getClass()) {
            $columnsToJoin[] = Product::F_GROUP_KEY;
            $columnsToJoin[] = Product::F_COLOR;
        }
        $criteria->_join($model, [ObjectTranslation::F_ID_OBJECT => "id"], "E", $columnsToJoin);

        /*if ($model == Property::getClass()) {
            $criteria->_join(Category::getClass(), ["E:" . Property::F_CATEGORY_ID => "id"], "C", [Category::F_NAME]);
        }*/

        if ($data["onlyEmpty"]) {
            $criteria->_value([null, ""], Criteria::C_IN);
        }

        $result = $criteria->find()->toArray(DataSet::AS_ARRAY);

        //add sku
        $columns = [
            ObjectTranslation::F_ID,
            ObjectTranslation::F_FIELD,
        ];
        $columns[] = ObjectTranslation::F_SOURCE;
        $columns[] = ObjectTranslation::F_VALUE;
        if ($data["withEnglishValues"] && $data["lang"] != "gb") {
            $columns[] = "english_value";
            $englishTranslations = ObjectTranslation::get()
                ->setColumns([ObjectTranslation::F_VALUE, ObjectTranslation::F_ID_OBJECT, ObjectTranslation::F_CLASS, ObjectTranslation::F_FIELD])
                ->_class($class, Criteria::C_LIKE)
                ->_lang("gb")
                ->find();
            $ETA = [];
            foreach ($englishTranslations as $et) {
                $ETA[$et[ObjectTranslation::F_ID_OBJECT] . "_" . $et[ObjectTranslation::F_FIELD]] = $et[ObjectTranslation::F_VALUE];
            }
        }
        if ($model == Product::getClass()) {
            $columns[] = "sku";
        }

        foreach ($columns as $columnNumber => $columnName) {
            $sh->setCellValueByColumnAndRow($columnNumber, 1, $columnName);
        }
        foreach ($result as $index => $r) {
            foreach ($columns as $key => $c) {
                switch ($c) {
                    case "english_value":
                        $sh->setCellValueByColumnAndRow($key, $index + 2, $ETA[$r[ObjectTranslation::F_ID_OBJECT] . "_" . $r[ObjectTranslation::F_FIELD]] ?? "");
                        break;
                    case "sku":
                        $sh->setCellValueByColumnAndRow($key, $index + 2, ($r["E:group_key"] . "-" . $r["E:color"]));
                        break;
                    default:
                        $sh->setCellValueByColumnAndRow($key, $index+2, $r[$c]);
                        break;
                }
            }
        }

        $sh->setTitle('Tłumaczenia ');
        $objPHPExcel->setActiveSheetIndex(0);
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
        if ($data["language"] == null){
            return [
                "status" => "fail",
            ];
        }

        //print_r($_FILES["data"]["tmp_name"]["files"][0]["nativeObj"]);
        //die();

        /** @var UploadedFile $fileObj */
        $fileObj = $_FILES["data"]["tmp_name"]["files"][0]["nativeObj"];

        $currentDate = date("Y-m-d");
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
        $sheetColumns = $sheetData[0];

        $fieldMap = [
            ObjectTranslation::F_ID,
            ObjectTranslation::F_FIELD,
            ObjectTranslation::F_SOURCE,
            ObjectTranslation::F_VALUE,
        ];
        $uploadedColumns = [];
        foreach ($sheetColumns as $column => $value) {
            if (in_array($value, $fieldMap)) {
                $uploadedColumns[$value] = $column;
            }
        }
        if (count($uploadedColumns) != count($fieldMap)) {
            print_r("error - document structure is wrong");
            exit();
        }

        $t = ObjectTranslation::getTable();
        $db = $project->getDB();

        $stm = $db->prepare("update $t set value=?  where id=? and lang=?");
        foreach ($sheetData as $row) {
            //print_r($row[0]);
            if ($row[0]) {
                $stm->execute([
                    $row[$uploadedColumns[ObjectTranslation::F_VALUE]],
                    $row[$uploadedColumns[ObjectTranslation::F_ID]],
                    $data["language"],
                ]);
            }
        }

        move_uploaded_file($fileObj, $target);
        return [
            "status" => "done",
        ];
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

        $currentDate = date("Y-m-d");
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
    public function history(){
        $dir = "data/translate_object_uploads";

        $files = scandir($dir);

        $returnData = [];

        foreach ($files as $file){
            $slicedFile = explode("_", $file);
            if (count($slicedFile) >= 3){
                $returnData[] = [
                    "full_name" => $file,
                    "language" => explode(".", $slicedFile[3])[0],
                    "user" => $slicedFile[2],
                    "date" => $slicedFile[0],
                    "time" => $slicedFile[1],
                ];
            }
        }

        return[
            "countAll" => count($files) - 2,
            "data" => $returnData,
            "debug" => false,
        ];
    }
}
