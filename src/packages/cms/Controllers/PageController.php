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
use Arrow\Kernel;
use Arrow\Media\Models\MediaAPI;
use Arrow\ORM\Persistent\DataSet;
use Arrow\ORM\Persistent\Criteria;
use Arrow\Translations\Models\Country;
use Arrow\Translations\Models\Language;
use Arrow\Translations\Models\Translations;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
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
        $user = Auth::getDefault()->getUser();
        $container = Kernel::getProject()->getContainer();

        /** @var Session $session */
        $session = $container->get(Session::class);

        if ($user->isInGroup("Administratos") || $user->isInGroup("Developers")) {
            $this->country = "pl";
        }

        $data["language"] = $session->get("language", "pl");
        Translations::setupLang($data["language"]);

        if ($user->isInGroup("Partnerzy sprzedaży") || $user->isInGroup("Tłumacz")) {
            $this->country = substr($user->_login(), -2);
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
     * @Route("")
     */
    public function index()
    {
        $editEnabled = $this->country == "pl" ? true : false;

        $pagesList = Page::get()
            ->setColumns(["name"])
            ->_type("folder")
            ->find();

        return [
            "editEnabled" => $editEnabled,
            "language" => $this->country,
            "containers" => $pagesList,
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

        if ($this->country !== "pl") {
            Translations::translateObjectsList($data["data"], Page::class, $this->country);
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
        $page = Page::get()
            ->findByKey($request->get("key"));

        if ($request->get("language") == null) {
            Translations::setupLang($this->country);
        } else {
            Translations::setupLang($request->get("language"));
        }

        Translations::translateObjectsList([$page]);

        //Translations::translateObjectsList([$page], Page::class, $request->get("language"));

        $pagData = $page->getData();

        $pagesList = Page::get()
            ->setColumns(["name"])
            ->_type("folder")
            ->find();

        if ($this->country !== "pl") {
            if ($this->country == "ua") {
                $langs = Language::get()
                    ->_code(["ua", "ru"], Criteria::C_IN)
                    ->setColumns(["name", "code"])
                    ->find();
            } else if ($this->country == "ru") {
                $langs = Language::get()
                    ->_code(["ru", "gb"], Criteria::C_IN)
                    ->setColumns(["name", "code"])
                    ->find();
            } else if ($this->country == "by") {
                $langs = Language::get()
                    ->_code(["by", "ru"], Criteria::C_IN)
                    ->setColumns(["name", "code"])
                    ->find();
            } else {
                $langs = Language::get()
                    ->_code($this->country)
                    ->setColumns(["name", "code"])
                    ->find()
                    ->toPureArray();

                foreach ($langs as $key => $item) {
                    $langs[$key]["name"] = Translations::translateText($langs[$key]["name"]);
                }
            }

        } else {
            $langs = Country::get()
                ->setColumns(["name", "code"])
                ->find();
        }

        $pagData["files"] = FormHelper::bindFilesToForm($page);

        $currentLengauge = $this->country == "pl" ? "pl" : $this->country;

        $editEnabled = $this->country == "pl" ? true : false;

        $contentTypes = array_merge(["default" => "---"], Page::getContentTypes());

        $this->json([
            "language" => $currentLengauge,
            "editEnabled" => $editEnabled,
            "page" => $pagData,
            "parents" => $pagesList,
            "languages" => $langs,
            "contentTypes" => $contentTypes
        ]);
    }

    /**
     * @Route("/save")
     */
    public function save(Request $request)
    {
        $data = $request->get('page');
        $uploaded = !empty($_FILES) ? FormHelper::getOrganizedFiles()['page']["files"] : [];
        if (array_key_exists("files", $data)) {
            $files = $data["files"];
        } else {
            $files = ["image" => []];
        }

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

        if ($this->country !== "pl") {
            if (strtoupper($this->country) !== $request->get("language")) {
                if ($request->get("language") == "ru") {
                    Translations::saveObjectTranslation($obj, $data, $request->get("language"));
                } else {
                    Translations::saveObjectTranslation($obj, $data, $this->country);
                }
            } else {
                throw new \Exception('Your language is not correct. Please change it to "' . $this->country . '"');
            }
        } else {
            if ($request->get("language") == null) {
                Translations::saveObjectTranslation($obj, $data, $request->get("pl"));
            }
            Translations::saveObjectTranslation($obj, $data, $request->get("language"));
        }


        FormHelper::bindFilesToObject($obj, $files, $uploaded);


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
        $data = $request->get("data");
        $validator = Validator::create($data)
            ->required([
                "name", "parent_id", "type"
            ]);

        if(!$validator->check()){
            return $validator->response();
        }

        $page = Page::create(
            [
                "parent_id" => $data["parent_id"],
                "name" => $data["name"],
                Page::F_TYPE => $data["type"],
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
