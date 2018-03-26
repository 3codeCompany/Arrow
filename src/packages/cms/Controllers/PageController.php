<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 06.09.2017
 * Time: 03:43
 */

namespace Arrow\CMS\Controllers;


use App\Controllers\BaseController;
use App\Models\Translations\Helper;
use Arrow\Access\Models\Auth;
use Arrow\CMS\Models\Persistent\Page;
use Arrow\Common\Layouts\ReactComponentLayout;

use Arrow\Common\Models\Helpers\FormHelper;
use Arrow\Common\Models\Helpers\TableListORMHelper;
use Arrow\Common\Models\Helpers\Validator;
use Arrow\Media\Models\MediaAPI;
use Arrow\ORM\Persistent\DataSet;
use Arrow\ORM\Persistent\Criteria;
use Arrow\Translations\Models\Language;
use Arrow\Translations\Models\Translations;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PageController
 * @package Arrow\CMS\Controllers
 * @Route("/pages")
 */
class PageController extends BaseController
{
    private $user;
    private $country = "pl";

    public function __construct(Auth $auth)
    {
        $this->user = $auth->getUser();
        if ($this->user->isInGroup("Partnerzy sprzedaży")) {
            $this->country = substr($this->user->_login(), -2);
            Translations::setupLang($this->country);
        }
    }

    private function addAccessCondition(Criteria $criteria, $key)
    {
        switch ($criteria->getModel()) {
            case Page::class:
                $criteria->c(Page::F_COUNTRY, ["all", $this->country], Criteria::C_IN);
                break;
        }
    }


    /**
     * @Route("/index")
     */
    public function index()
    {
        $editEnabled = $this->country == "pl" ? true : false;

        return [
            "editEnabled" => $editEnabled,
        ];
    }

    /**
     * @Route("/asyncIndex")
     */
    public function list()
    {
        $criteria = Page::get()
            ->_id(1, Criteria::C_GREATER_THAN);

        if ($this->country !== "pl") {
            $this->addAccessCondition($criteria, "");
        }

        $helper = new TableListORMHelper();

        $helper->addDefaultOrder(Page::F_SORT);
        $helper->setFetchType(DataSet::AS_OBJECT);
        $data = $helper->getListData($criteria);

        if ($this->country == "ua") {
            Translations::translateObjectsList($data["data"], false, $this->country);
        }
        //MediaAPI::prepareMedia($data["data"]);
        return $this->json($data);
    }

    /**
     * @Route("/delete")
     */
    public function delete(Request $request)
    {
        $data = Page::get()
            ->findByKey($request->get("key"));
        MediaAPI::removeFilesFromObject($data);
        $data->delete();
        $this->json([true]);
    }

    /**
     * @Route("/get")
     */
    public function get(Request $request)
    {
        $data = Page::get()
            ->findByKey($request->get("key"));
        Translations::translateObjectsList([$data]);
        MediaAPI::prepareMedia([$data]);
        $this->json($data);
    }

    /**
     * @Route("/edit")
     */
    public function edit(Request $request)
    {
        $page = $request->get("key") != 1 ? Page::get()
            ->findByKey($request->get("key")) : [];

        if ($this->country !== "pl") {
            Translations::setupLang($this->country);
        } else {
            if ($request->get("language") !== null) {
                Translations::setupLang($request->get("language"));
            }
        }

        Translations::translateObjectsList([$page]);
        $pagData = $page->getData();


        $pagesList = Page::get()
            ->setColumns(["name"])
            ->_type("folder")
            ->find();


        if ($this->country !== "pl") {
            $langs = Language::get()
                ->_code($this->country)
                ->setColumns(["name", "code"])
                ->find()
                ->toPureArray();

            foreach ($langs as $key => $item) {
                $langs[$key]["name"] = Translations::translateText($langs[$key]["name"]);
            }

        } else {
            $langs = Language::get()
                ->setColumns(["name", "code"])
                ->find();
        }

        $pagData["files"] = FormHelper::bindFilesToForm($page);

        $currentLengauge = $this->country == "pl" ? "pl" : $this->country;

        $editEnabled = $this->country == "pl" ? true : false;

        $this->json([
            "language" => $currentLengauge,
            "editEnabled" => $editEnabled,
            "page" => $pagData,
            "parents" => $pagesList,
            "languages" => $langs
        ]);
    }

    /**
     * @Route("/save")
     */
    public function save(Request $request)
    {
        $data = $request->get('page');
        unset($data["files"]);

        $validator = Validator::create($data)
            ->required(['name',]);

        if (!$validator->check()) {
            return $this->json($validator->response());
        }

        if (!isset($data["id"])) {
            $obj = Page::create($data);
        } else {
            $obj = Page::get()->findByKey($data["id"]);
        }

        //print_r($this->country);
        if ($this->country !== "pl") {
            if (strtoupper($this->country) !== $request->get("language")) {
                Translations::saveObjectTranslation($obj, $data, $this->country);
            } else {
                throw new \Exception('Your language is not correct. Please change it to "' . $this->country . '"');
            }
        } else {
            if ($request->get("language") == null) {
                Translations::saveObjectTranslation($obj, $data, $request->get("pl"));
            }
            Translations::saveObjectTranslation($obj, $data, $request->get("language"));
        }

        //FormHelper::replaceObjectFiles($obj, "page");
        $obj->updateTreeSorting();

        //FormHelper::bindFilesToObject($obj, $files, $uploaded);

        $this->json([$obj->_id()]);
    }

    /**
     * @Route("/add")
     */
    public function add(Request $request)
    {
        $page = Page::create(
            [
                "parent_id" => 15,
                "name" => $request->get("data")["name"],
                Page::F_TYPE => Page::TYPE_PAGE,
                "country" => $this->country,
            ]
        );
        $page->updateTreeSorting();

        $this->json([1]);
    }

    /**
     * @Route("/moveDown")
     */
    public function moveDown(Request $request)
    {
        $obj = Page::get()->findByKey($request->get("key"));
        $obj->moveDown();
        $this->json();
    }

    /**
     * @Route("/moveUp")
     */
    public function moveUp(Request $request)
    {
        $obj = Page::get()->findByKey($request->get("key"));
        $obj->moveUp();
        $this->json();
    }

    /**
     * @Route ( "/updateLang" )
     */
    public function updateLang()
    {
        $arr = [
        ];
        Translations::setupLang("ua");
        Translations::translateTextArray($arr);

        return [true];
    }

}