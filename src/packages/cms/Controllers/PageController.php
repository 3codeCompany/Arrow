<?php
/**
 * Created by PhpStorm.
 * User: Adrian Śliwka
 * Date: 20.02.2018
 * Time: 12:38
 */

namespace Arrow\CMS\Controllers;

use App\Controllers\BaseController;
use Arrow\Common\Models\Helpers\TableListORMHelper;
use Arrow\Common\Models\Helpers\Validator;
use Arrow\ORM\Persistent\Criteria;
use Arrow\Shop\Models\Persistent\Category;
use Arrow\Shop\Models\Persistent\Property;
use Arrow\Shop\Models\Persistent\PropertyCategoryConnection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PageController
 * @package Arrow\Cms\Controllers
 * @Route("/pages")
 */
class PageController extends BaseController
{
    /**
     * @Route( "" )
     */
    public function index()
    {
        return [
            true
        ];
    }


    /**
     * @Route( "/asyncIndex" )
     */
    public function asyncListData()
    {
        $criteria = Category::get()
            ->order(Category::F_SORT, Criteria::O_ASC);


        $helper = new TableListORMHelper();


        return $helper->getListData($criteria);
    }


    /**
     * @Route( "/create" )
     */
    public function create()
    {
        $names = Category::get()
            ->order(Category::F_SORT, Criteria::O_ASC)
            ->findAsFieldArray("name");
        $depths = Category::get()
            ->order(Category::F_SORT, Criteria::O_ASC)
            ->findAsFieldArray("depth");
        $ids = Category::get()
            ->order(Category::F_SORT, Criteria::O_ASC)
            ->findAsFieldArray("id");

        $arr = [];
        $i = 0;
        foreach ($names as $key){
            $arr[$i]["name"] = $key;
            $i++;
        }
        $i = 0;
        foreach ($depths as $key){
            $arr[$i]{"depth"} = $key;
            $i++;
        }
        $i = 0;
        foreach ($ids as $key){
            $arr[$i]{"id"} = $key;
            $i++;
        }


        $propName = Property::get()
            ->findAsFieldArray("name");
        $propId = Property::get()
            ->findAsFieldArray("id");

        $prepareArr  = [];
        $i = 0;
        foreach ($propId as $row){
            $prepareArr[$i]["id"] = $row;
            $i++;
        }
        $i = 0;
        foreach ($propName as $row){
            $prepareArr[$i]["name"] = $row;
            $i++;
        }

        return [
            "depths" => $arr,
            "properties" => $prepareArr,
        ];
    }


    /**
     * @Route( "/store" )
     */
    public function store(Request $request)
    {
        $data = $request->get("data");

        $validator = Validator::create($data)
            ->required([])
        ;

        $getParent = Category::get()
            ->findByKey($data["parent_id"]);

        $data["sort"] = $getParent["sort"] + 1;
        $data["depth"] = $getParent["depth"] + 1;

        if($validator->fails()){
            return $validator->response();
        }else{
            $object = Category::create( $data );

            return [
                "id" => $object->_id()
            ];
        }
    }

    /**
     * @Route( "/{key}/storeProperties" )
     */
    public function storeProperties(Request $request)
    {
        $data = $request->get("data");

        return [
            "return" => $data
        ];
    }

    /**
     * @Route( "/{key}/edit" )
     */
    public function edit(int $key)
    {
        $object = Category::get()
            ->findByKey( $key );

        $names = Category::get()
            ->order(Category::F_SORT, Criteria::O_ASC)
            ->findAsFieldArray("name");
        $depths = Category::get()
            ->order(Category::F_SORT, Criteria::O_ASC)
            ->findAsFieldArray("depth");
        $ids = Category::get()
            ->order(Category::F_SORT, Criteria::O_ASC)
            ->findAsFieldArray("id");

        $arr = [];
        $i = 0;
        foreach ($names as $key){
            $arr[$i]["name"] = $key;
            $i++;
        }
        $i = 0;
        foreach ($depths as $key){
            $arr[$i]{"depth"} = $key;
            $i++;
        }
        $i = 0;
        foreach ($ids as $key){
            $arr[$i]{"id"} = $key;
            $i++;
        }


        $propName = Property::get()
            ->findAsFieldArray("name");
        $propId = Property::get()
            ->findAsFieldArray("id");

        $prepareArr  = [];
        $i = 0;
        foreach ($propId as $row){
            $prepareArr[$i]["id"] = $row;
            $i++;
        }
        $i = 0;
        foreach ($propName as $row){
            $prepareArr[$i]["name"] = $row;
            $i++;
        }

        return [
            "object" => $object,
            "properties" => $prepareArr,
            "depths" => $arr,
        ];
    }

    /**
     * @Route( "/{key}/update" )
     */
    public function update(int $key, Request $request)
    {
        $data = $request->get("data");

        $validator = Validator::create($data)
            ->required([])
        ;

        $getParent = Category::get()
            ->findByKey($data["parent_id"]);

        $data["sort"] = $getParent["sort"];
        $data["depth"] = $getParent["depth"] + 1;

        if($validator->fails()){
            return $validator->response();
        }else{

            $object = Category::get()
                ->findByKey( $key )
                ->setValues($data)
                ->save()
            ;

            return [
                "id" => $object->_id()
            ];
        }
    }

    /**
     * @Route("/{key}/toggleActive")
     */
    public function toggleActive(int $key, Request $request){
        $data = $request->get("active");
        if($data == 1){
            $data = 0;
        } else {
            $data = 1;
        }

        $saveArray = [
            'active' => $data
        ];
        $object = Category::get()
            ->findByKey($key)
            ->setValues($saveArray)
            ->save();

        return[
            "id" => $object
        ];
    }

    /**
     * @Route( "/{key}/moveUp" )
     */
    public function moveUp(int $key){
        $el = Category::get()->findByKey($key);

        $target = Category::get()
            ->_sort($el->_sort(), Criteria::C_LESS_THAN)
            ->order(Category::F_SORT, Criteria::O_DESC)
            ->findFirst();


        $elSort = $el->_sort();
        $targetSort = $target->_sort();

        $el["sort"] = $targetSort;
        $target["sort"] = $elSort;

        $el->save();
        $target->save();

        return [];
    }

    /**
     * @Route( "/{key}/moveDown" )
     */
    public function moveDown(int $key){
        $el = Category::get()->findByKey($key);

        $target = Category::get()
            ->_sort($el->_sort(), Criteria::C_GREATER_THAN)
            ->order(Category::F_SORT, Criteria::O_ASC)
            ->findFirst();


        $elSort = $el->_sort();
        $targetSort = $target->_sort();

        $el["sort"] = $targetSort;
        $target["sort"] = $elSort;

        $el->save();
        $target->save();

        return [];
    }

    // test
    /**
     * @Route( "/propArray" )
     */
    public function propArray(){
        $names = Category::get()
            ->findAsFieldArray("name");
        $depths = Category::get()
            ->findAsFieldArray("depth");

        $arr = [];
        $i = 0;

        foreach ($names as $key){
            $arr[$i]["name"] = $key;
            $i++;
        }

        $i = 0;
        foreach ($depths as $key){
            $arr[$i]{"depth"} = $key;
            $i++;
        }

        $i = 0;
        $completedArr = [];
        foreach ($arr as $row){
//            if($arr[$i]["depth"] == 0){
//                $arr[$i]["depth"] = "---";
//            }
            $completedArr[$arr[$i]["name"]] = $arr[$i]["depth"];
            $i++;
        }


        return $completedArr;
    }
}