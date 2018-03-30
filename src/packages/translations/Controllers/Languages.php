<?php

namespace Arrow\Translations\Controllers;


use App\Controllers\BaseController;
use Arrow\Common\Layouts\ReactComponentLayout;
use Arrow\Common\Models\Helpers\Validator;
use Arrow\Common\Models\Helpers\TableListORMHelper;
use Arrow\ORM\Persistent\Criteria;
use Arrow\Translations\Models\Language;
use Arrow\Translations\Models\LanguageText;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class Languages
 * @package Arrow\Translations\Controllers
 * @route("/languages")
 */
class Languages extends BaseController
{

    /**
     * @Route("/index")
     */
    function index()
    {
        return [];
    }

    /**
     * @Route("/list")
     */
    public function list()
    {
        $ctit = Language::get();
        $helper = new TableListORMHelper();

        $helper->addDefaultOrder(Language::F_NAME);
        return $helper->getListData($ctit);
    }

    /**
     * @Route("/get")
     */
    public function get(Request $request)
    {
        $data = Language::get()
            ->findByKey($request->get("key"));
        return $data;
    }

    /**
     * @Route("/save")
     */
    public function save(Request $request)
    {

        $data = $request->get('data');
        $validator = Validator::create($data)
            ->required(['name', 'code']);

        if (!$validator->check()) {
            return $this->json($validator->response());
        }

        if (!isset($data["id"])) {
            $obj = Language::create($data);
        } else {
            $obj = Language::get()->findByKey($data["id"]);
            $obj->setValues($data);
        }
        $obj->save();

        return [$obj->_id()];
    }

    /**
     * @Route("/delete")
     */
    public function delete(Request $request)
    {
        $data = Language::get()
            ->findByKey($request->get("key"));
        $data->delete();
        return [true];
    }

    /**
     * @Route("/dumpLangFile/{langCode}")
     */
    public function dumpLangFile($langCode)
    {

        $text = LanguageText::get()
            ->_lang($langCode)
            ->_value("", Criteria::C_NOT_EQUAL)
            ->findAsFieldArray(LanguageText::F_VALUE, LanguageText::F_ORIGINAL);

        $text["language"] = "en";

        return $text;

    }

}
