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
use Arrow\CMS\Models\Persistent\Banner;
use Arrow\CMS\Models\Persistent\Page;
use Arrow\Common\Layouts\ReactComponentLayout;

use Arrow\Common\Models\Helpers\FormHelper;
use Arrow\Common\Models\Helpers\TableListORMHelper;
use Arrow\Common\Models\Helpers\Validator;
use Arrow\Kernel;
use Arrow\Media\Models\Element;
use Arrow\Media\Models\ElementConnection;
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
 * @Route("/banners")
 */
class BannerController extends BaseController
{
    private $user;
    private $country = "pl";
    private $places = [];
    private $countries = [];

    public function __construct(Auth $auth)
    {
        $countries = Country::get()
            ->find()
            ->toPureArray()
        ;
        foreach ($countries as $country)
        {
            $this->countries[$country["code"]] = $country["name"];
        }

        $places = Banner::get()
            ->addGroupBy(Banner::F_PLACE)
            ->findAsFieldArray(Banner::F_PLACE)
        ;
        $placesMap = [
            "content" => "Archiwum",
            "E_content" => "Esotiq kafelki", //
            "E_inspire" => "Esotiq kolekcja 1", // E_inspire
            "E_sale" => "Esotiq kolekcja 2", // E_sale

            "E_orderEnd" => "Esotiq podziękowanie za zamówienie",
            "E_slider" => "Esotiq slider",
            "H_big" => "Henderson cała szerokość",
            "H_content" => "Henderson treść",
            "H_logo" => "Henderson logo",
            "H_slider" => "Henderson slider",
            "slider" => "Slider",
            "F_content" => "Finalsale treść",
            "F_slider" => "Finalsale slider",
            "" => "",
        ];
        foreach ($places as $key => $place)
        {
            $this->places[$place] = $placesMap[$place];
        }

        $user = Auth::getDefault()->getUser();
        $container = Kernel::getProject()->getContainer();

        /** @var Session $session */
        $session = $container->get(Session::class);

        if ($user->isInGroup("Administratos") || $user->isInGroup("Developers")) {
            $this->country = "pl";
        }

        $data["language"] = $session->get("language", "pl");
        Translations::setupLang($data["language"]);

        if ($user->isInGroup("Partnerzy sprzedaży")|| $user->isInGroup("Tłumacz")) {
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
        return [
            "places" => $this->places,
        ];
    }

    /**
     * @Route("/asyncIndex")
     */
    public function list()
    {
        $criteria = Banner::get();

        $helper = new TableListORMHelper();

        $helper->addFilter("place", function ($empty, $filter) use ($criteria){
            $revertedPlaces = [];
            foreach ($this->places as $key => $place)
            {
                $revertedPlaces[$place] = $key;
            }


            if (array_key_exists($filter["value"][0], $revertedPlaces)){
                $criteria->_place($revertedPlaces[$filter["value"][0]]);
            }
        });

        $helper->addFilter("file_name", function ($empty, $filter) use ($criteria){
            $mediaElement = Element::get()
                ->_file("%" . $filter["value"] . "%", Criteria::C_LIKE)
                ->find()
                ->toPureArray()
            ;

            if (count($mediaElement) > 0) {
                $mediaElementConnection = ElementConnection::get()
                    ->_elementId($mediaElement[0]["id"])
                    ->find()
                    ->toPureArray()
                ;
                $criteria->_id($mediaElementConnection[0]["object_id"]);
            } else {
                $criteria->_id(99999999);
            }
        });
        $criteria->order("sort", "desc");

        return $helper->getListData($criteria);
    }

    /**
     * @Route("/{key}/edit")
     */
    public function edit(int $key)
    {
        $banner = Banner::get()
            ->findByKey($key)
        ;

        $objData = $banner->getData();

//        if (ARROW_IN_DEV_STATE) {
//            echo "<pre>";
//            print_r(FormHelper::bindFilesToForm($banner));
//        }

        $objData["files"] = FormHelper::bindFilesToForm($banner);

        return [
            "object" => $objData,
            "countries"=> $this->countries,
            "places" => $this->places,
        ];
    }

    /**
     * @Route("/{key}/update")
     */
    public function update(int $key, Request $request)
    {
        $data = $request->get("data");

        if (isset($data["sortOnly"])) {
            $e = $data["banner"];
            $s = $data["sort"];
            $banner = Banner::get()
                ->findByKey($e)
            ;
            $banner->setValue("sort", $s);
            $banner->save();
            return[true];
        } else {

            $uploaded = !empty($_FILES) ? FormHelper::getOrganizedFiles()['data']["files"] : [];
            if (array_key_exists("files", $data)) {
                $files = $data["files"];
            } else {
                $files = ["image" => []];
            }

            unset($data["files"]);

            $banner = Banner::get()
                ->findByKey($key);

//        if (ARROW_IN_DEV_STATE) {
//            $files = [
//                "images" => []
//            ];
//        }

            FormHelper::bindFilesToObject($banner, $files, $uploaded);

            $banner->setValues($data);
            $banner->save();

            return [true];
        }
    }

    /**
     * @Route("/{key}/copy")
     */
    public function copy(int $key, Request $request)
    {
        $data = $request->get("data");
        $uploaded = !empty($_FILES) ? FormHelper::getFixedFilesArray()["data"]["files"] : [];
        $files = $data["files"];
        unset($data["files"]);

        $data["id"] = null;
        $data["title"] = "Kopia " . $data["title"];

        $banner = Banner::create($data);

        return[true];
    }

    /**
     * @Route("/{key}/delete")
     */
    public function delete(int $key)
    {
        $banner = Banner::get()
            ->findByKey($key)
        ;
        $banner->delete();

        return[true];
    }

    /**
     * @Route("/{key}/sortUpdate")
     */
    public function sortUpdate(Request $request)
    {
        $e = $request->get("banner");
        $s = $request->get("sort");
        $banner = Banner::get()
            ->findByKey($e)
        ;
        $banner->setValue("sort", $s);
        $banner->save();
        return[true];
    }

    /**
     * @Route("/{key}/moveUp")
     */
    public function moveUp(int $key)
    {
        $banner = Banner::get()
            ->findByKey($key)
        ;

        $el = Banner::get()
            ->_sort($banner->_sort(), Criteria::C_GREATER_THAN)
            ->order("sort", "asc")
            ->findFirst()
        ;

        $banner->setValue("sort", $el->_sort() + 1)->save();

        return[true];
    }

    /**
     * @Route("/{key}/moveDown")
     */
    public function moveDown(int $key)
    {
        $banner = Banner::get()
            ->findByKey($key)
        ;

        $el = Banner::get()
            ->_sort($banner->_sort(), Criteria::C_LESS_THAN)
            ->order("sort", "desc")
            ->findFirst()
        ;

        $banner->setValue("sort", $el->_sort() - 1)->save();

        return[true];
    }

    /**
     * @Route("/{key}/active")
     */
    public function active(int $key, Request $request)
    {
        $active = (int)$request->get("active") == "1" ? "0" : "1";
        $banner = Banner::get()
            ->findByKey($key)
        ;
        $banner->setValue("active", $active)->save();

        return[true];
    }

    /**
     * @Route("/create")
     */
    public function create()
    {
        return[
            "places" => $this->places,
            "countries" => $this->countries,
        ];
    }

    /**
     * @Route("/store")
     */
    public function store(Request $request)
    {
        $data = $request->get("data");
        $uploaded = !empty($_FILES) ? FormHelper::getOrganizedFiles()['data']["files"] : [];
        $files = $data["files"];
        unset($data["files"]);

        $banner = Banner::create($data);

        FormHelper::bindFilesToObject($banner, $files, $uploaded);

        $banner->save();

        $ban = Banner::get()->findByKey($banner->_id());
        $ban->setValue("sort", $banner->_id())->save();

        return[true];
    }
}
