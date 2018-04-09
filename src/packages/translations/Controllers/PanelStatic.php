<?php

namespace Arrow\Translations\Controllers;


use App\Controllers\BaseController;
use App\Models\Persistent\TransactionText;
use Arrow\Common\Layouts\ReactComponentLayout;

use Arrow\Common\Models\Helpers\TableListORMHelper;
use Arrow\Models\Dispatcher;
use Arrow\Models\Operation;
use Arrow\Models\Project;
use Arrow\Models\View;
use Arrow\ORM\Persistent\Criteria,
    \Arrow\Access\Models\Auth,
    \Arrow\RequestContext;
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

use function file_get_contents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PanelStatic
 * @package Arrow\Translations\Controllers
 * @Route("/static")
 */
class PanelStatic extends BaseController
{
    private $user;
    public $country = "pl";

    public function __construct()
    {
        $this->user = Auth::getDefault()->getUser()->_login();
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
        $db->query("DELETE n1 FROM common_lang_texts n1, common_lang_texts n2 WHERE n1.id > n2.id AND n1.hash=n2.hash and n1.lang=n2.lang");

        return [
            'language' => Language::get()->findAsFieldArray(Language::F_NAME, Language::F_CODE),
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

        $user = Auth::getDefault()->getUser()->_login();
        $tmp = explode("_", $user);
        if (count($tmp) == 2) {
            $c->_lang($tmp[1]);
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
    public function uploadLangFile()
    {
        $file = ($_FILES["data"]["tmp_name"]["files"][0]["nativeObj"]);


        $this->downloadLangFile();

        print_r($backupName);
        die();

        $currentDate = date("d-m-Y");
        $currentTime = date("H:i:s");
        $backupName = $currentDate . "_" . $currentTime . "_" . $this->user . ".xls";
        $target = "data/translate_uploads/" . $backupName;
        move_uploaded_file($file, $target);


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

        print_r("works here");

//        $table = $table = LanguageText::getTable();
//        $db = Project::getInstance()->getDB();
//
//        $query = $db->prepare("update $table set value=? where id=?");
//        $db->beginTransaction();
//
//        foreach ($sheetData as $row) {
//            $query->execute([
//                $row[2],
//                $row[0]
//            ]);
//        }
//        $db->commit();

        $this->json();
    }

    /**
     * @Route("/downloadLangFile")
     */
    public function downloadLangFile(Request $request)
    {
        //$data = json_decode($request->get("payload"), true);

        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("CMS");
        $sh = $objPHPExcel->setActiveSheetIndex(0);

        $criteria = LanguageText::get()
            ->_lang($request->get("lang"));

        if ($request->get("onlyEmpty")) {
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
        header('Content-Disposition: attachment;filename="tłumaczenia_' . $request->get("lang"). '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save("php://output");
        exit;

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
        $obj = LanguageText::get()
            ->findByKey($request->get("key"));

        $obj->setValue(LanguageText::F_VALUE, $request->get("newValue"));
        $obj->save();


        $this->json([1]);
    }


}

