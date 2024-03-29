<?php

namespace Arrow\Translations\Controllers;

use App\Controllers\BaseController;
use App\Models\Persistent\TransactionText;
use Arrow\Common\Layouts\ReactComponentLayout;

use Arrow\Common\Models\Helpers\TableListORMHelper;
use Arrow\Media\Models\Helpers\DownloadHelper;
use Arrow\Models\Dispatcher;
use Arrow\Models\Operation;
use Arrow\Models\Project;
use Arrow\Models\View;
use Arrow\ORM\Persistent\Criteria, Arrow\Access\Models\Auth, Arrow\RequestContext;
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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Shared\Models\Cache\RedisCacheConnector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PanelStatic
 * @package Arrow\Translations\Controllers
 * @Route("/static")
 */
class PanelStatic extends BaseController
{
    protected $user;
    public $country = "pl";

    public function __construct()
    {
        $this->user = Auth::getDefault()
            ->getUser()
            ->_login();
        $tmp = explode("_", $this->user);

        if (count($tmp) >= 2) {
            $this->country = $tmp[1];
        }
    }

    /**
     * @Route("/index")
     */
    public function index()
    {
        $db = Project::getInstance()->getDB();
        $t = LanguageText::getTable();
        $db->query(
            "DELETE n1 FROM common_lang_texts n1, common_lang_texts n2 WHERE n1.id > n2.id AND n1.hash=n2.hash and n1.lang=n2.lang"
        );

        return [
            "language" => Language::get()->findAsFieldArray(Language::F_NAME, Language::F_CODE),
            "country" => $this->country,
        ];
    }

    /**
     * @Route("/list")
     */
    public function list()
    {
        $c = LanguageText::get();
        $helper = new TableListORMHelper();

        $user = Auth::getDefault()
            ->getUser()
            ->_login();
        $tmp = explode("_", $user);
        if (count($tmp) == 2) {
            if ($tmp[1] == "ua") {
                $c->_lang(["ua", "ru"], Criteria::C_IN);
            } elseif ($tmp[1] == "by") {
                $c->_lang(["by", "ru"], Criteria::C_IN);
            } else {
                $c->_lang($tmp[1]);
            }
        }

        $search = $helper->getInputData()["additionalConditions"]["search"];
        if ($search) {
            $c->addSearchCondition([LanguageText::F_ORIGINAL], "%{$search}%", Criteria::C_LIKE);
        }
        $data = $helper->getListData($c);

        //$data["debug"] = $c->find()->getQuery();
        //$helper->addDefaultOrder(Language::F_NAME);

        return $data;
    }

    /**
     * @Route("/uploadLangFile")
     */
    public function uploadLangFile(Request $request)
    {
        $data = $request->get("data");
        if ($data["language"] == null) {
            return [
                "status" => "fail",
            ];
        } else {
            $file = $_FILES["data"]["tmp_name"]["files"][0]["nativeObj"];

            $currentDate = date("d-m-Y");
            $currentTime = date("H:i:s");
            $backupName = $currentDate . "_" . $currentTime . "_" . $this->user . "_" . $data["language"] . ".xls";

            // file stored
            try {
                $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            } catch (\Exception $e) {
                die('Error loading file "' . pathinfo($file, PATHINFO_BASENAME) . '": ' . $e->getMessage());
            }

            $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, false);

            $table = LanguageText::getTable();
            $db = Project::getInstance()->getDB();

            $currLang = $data["language"];

            $query = $db->prepare("update $table set value=? where id=? and lang=?");
            $db->beginTransaction();

            foreach ($sheetData as $row) {
                $query->execute([$row[2], $row[0], $currLang]);
            }
            $db->commit();

            return [
                "status" => "done",
            ];
        }
    }

    /**
     * @Route("/downloadLangFile")
     */
    public function downloadLangFile(Request $request)
    {
        //$data = json_decode($request->get("payload"), true);

        $objPHPExcel = new Spreadsheet();
        $objPHPExcel->getProperties()->setCreator("CMS");
        $sh = $objPHPExcel->setActiveSheetIndex(0);

        $criteria = LanguageText::get()->_lang($request->get("lang"));

        if ($request->get("onlyEmpty")) {
            $criteria->_value([null, ""], Criteria::C_IN);
        }
        // Add some data

        $result = $criteria->find()->toArray(DataSet::AS_ARRAY);

        $columns = [LanguageText::F_ID, LanguageText::F_ORIGINAL, LanguageText::F_VALUE, LanguageText::F_MODULE];

        $sh->setCellValueByColumnAndRow(1, 1, "id");
        $sh->setCellValueByColumnAndRow(2, 1, "Orginał");
        $sh->setCellValueByColumnAndRow(3, 1, "Tłumaczenie");
        $sh->setCellValueByColumnAndRow(4, 1, "Moduł");
        foreach ($result as $index => $r) {
            foreach ($columns as $key => $c) {
                $sh->setCellValueByColumnAndRow($key + 1, $index + 2, $r[$c]);
            }

            //$sh->setCellValueByColumnAndRow($key +1 , $index, Reclaim::re$r[$c]);
        }
        $objPHPExcel
            ->getActiveSheet()
            ->getStyle("B1:D5000")
            ->getAlignment()
            ->setWrapText(true);

        foreach ($columns as $key => $c) {
            //$sh->getColumnDimensionByColumn($key)->setAutoSize(true);
            //$sh->getColumnDimensionByColumn($key)->setWidth(200);
        }

        $sh->getColumnDimensionByColumn(2)->setWidth(70);
        $sh->getColumnDimensionByColumn(3)->setWidth(70);

        // Rename worksheet
        $sh->setTitle("Tłumaczenia ");
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);

        $writer = new Xlsx($objPHPExcel);
        $name = "translations_{$request->get("lang")}.xls";
        $file = ARROW_CACHE_PATH . "/" . $name;
        $writer->save($file);

        $dh = new DownloadHelper();
        $response = $dh->getDownloadResponseFromPath($name, $file);
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @Route("/langBackUp")
     */
    public function langBackUp(Request $request)
    {
        //$data = json_decode($request->get("payload"), true);

        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("CMS");
        $sh = $objPHPExcel->setActiveSheetIndex(0);

        $criteria = LanguageText::get()->_lang($request->get("lang"));

        if ($request->get("onlyEmpty")) {
            $criteria->_value([null, ""], Criteria::C_IN);
        }
        // Add some data

        $result = $criteria->find()->toArray(DataSet::AS_ARRAY);

        $columns = [LanguageText::F_ID, LanguageText::F_ORIGINAL, LanguageText::F_VALUE, LanguageText::F_MODULE];

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
        $objPHPExcel
            ->getActiveSheet()
            ->getStyle("B1:D5000")
            ->getAlignment()
            ->setWrapText(true);

        foreach ($columns as $key => $c) {
            //$sh->getColumnDimensionByColumn($key)->setAutoSize(true);
            //$sh->getColumnDimensionByColumn($key)->setWidth(200);
        }

        $sh->getColumnDimensionByColumn(1)->setWidth(70);
        $sh->getColumnDimensionByColumn(2)->setWidth(70);

        $currentDate = date("d-m-Y");
        $currentTime = date("H:i:s");
        $backupName = $currentDate . "_" . $currentTime . "_" . $this->user . "_" . $request->get("lang") . ".xls";
        $target = "data/translate_backups/" . $backupName;

        // Rename worksheet
        $sh->setTitle("Tłumaczenia ");
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);
        // Redirect output to a client’s web browser (Excel5)
        header("Content-Type: application/vnd.ms-excel");
        header('Content-Disposition: attachment;filename="tłumaczeniaa_' . $request->get("lang") . '.xls"');
        header("Cache-Control: max-age=0");
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel5");
        $objWriter->save($target);
        exit();
    }

    /**
     * @Route("/delete")
     */
    public function delete(Request $request)
    {
        $elements = LanguageText::get()
            ->_id($request->get("keys"), Criteria::C_IN)
            ->find();

        foreach ($elements as $element) {
            $element->delete();
        }

        $this->json([true]);
    }

    /**
     * @Route("/inlineUpdate")
     */
    public function inlineUpdate(Request $request)
    {
        $obj = LanguageText::get()->findByKey($request->get("key"));

        RedisCacheConnector::connect();
        RedisCacheConnector::$adapter->invalidateTags(["erp-panel-translations"]);
        //RedisCacheConnector::$adapter->deleteItem("erp-panel-translations");
        //"erp_panel_translations" . $lang

        $obj->setValue(LanguageText::F_VALUE, $request->get("newValue"));
        $obj->save();


        RedisCacheConnector::connect();
        RedisCacheConnector::$adapter->invalidateTags(["erp-panel-translations"]);

        $this->json([1]);
    }

    /**
     * @Route("/history")
     */
    public function history()
    {
        $dir = "data/translate_uploads";

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
     * @Route("/singleValue/{originalValue}")
     */
    public function singleValue($originalValue, Request $request)
    {
        return [
            "language" => Language::get()->findAsFieldArray(Language::F_NAME, Language::F_CODE),
            "originalValue" => $originalValue,
            "country" => $this->country,
        ];
    }

    /**
     * @Route("/asyncSingleValue/{originalValue}")
     */
    public function asyncSingleValue($originalValue)
    {
        $c = LanguageText::get()->_hash(md5($originalValue));
        $result = $c->find();
        if($result->count() == 0){
            Translations::translateText($originalValue, "en");
        }

        $helper = new TableListORMHelper();

        $data = $helper->getListData($c);

        return $data;
    }

}
