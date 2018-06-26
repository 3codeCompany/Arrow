<?php
/**
 * Created by PhpStorm.
 * User: artur.kmera
 * Date: 22.06.2018
 * Time: 15:12
 */

namespace Arrow\Common\Controllers;

use Arrow\Common\Models\Dictionary;
use Arrow\Common\Models\Helpers\TableListORMHelper;
use Arrow\Common\Models\Helpers\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DictionaryController
 * @package Arrow\Common\Controllers
 * @Route("/dictionary")
 */
class DictionaryController
{

    /**
     * @Route("/list")
     * @Route("/list/{parentKey}")
     */
    public function index($parentKey = false)
    {
        $parent = $parentKey ? Dictionary::get()->findByKey($parentKey) : Dictionary::getRoot();

        return [
            "parent" => $parent,
            "path" => $parent->getPath(Dictionary::F_LABEL),
            "ancestors" => $parent->getAncestors()
        ];
    }


    /**
     * @Route("/listData/{parentKey}")
     */
    public function listData($parentKey)
    {
        $c = Dictionary::get()
            ->_parentId($parentKey);

        $helper = new TableListORMHelper();


        return $helper->getListData($c);

    }


    /**
     * @Route("/create/{parentKey}")
     */
    public function create($parentKey, Request $request)
    {

        $data = $request->get("data");

        $v = Validator::create($data)
            ->required("label");

        if ($v->fails()) {
            return $v->response();
        }

        Dictionary::create([
            Dictionary::F_PARENT_ID => $parentKey,
            Dictionary::F_LABEL => $data["label"],
            Dictionary::F_VALUE => $data["value"],
            Dictionary::F_DATA => $data["data"],
            Dictionary::F_SYSTEM_NAME => $data["system_name"],
        ]);


        Dictionary::updateTreeSorting();


        return [];

    }

    /**
     * @Route("/save/{parentKey}")
     */
    public function save($parentKey, Request $request)
    {

        $data = $request->get("data");

        $v = Validator::create($data)
            ->required("label");

        if ($v->fails()) {
            return $v->response();
        }

        $dic = Dictionary::get()->findByKey($data["id"]);

        $dic->setValues([
            Dictionary::F_PARENT_ID => $parentKey,
            Dictionary::F_LABEL => $data["label"],
            Dictionary::F_VALUE => $data["value"],
            Dictionary::F_DATA => $data["data"],
            Dictionary::F_SYSTEM_NAME => $data["system_name"],
        ]);


        $dic->save();
        Dictionary::updateTreeSorting();


        return [];

    }

    /**
     * @Route("/delete/{parentKey}")
     */
    public function delete($parentKey)
    {
        Dictionary::get()->findByKey($parentKey)->delete();
        return [
            true
        ];
    }

}