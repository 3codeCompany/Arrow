<?php
/**
 * Created by PhpStorm.
 * User: Adrian Śliwka
 * Date: 28.01.2019
 * Time: 09:36
 */

namespace Arrow\CMS\Controllers;


use App\Controllers\BaseController;
use Arrow\Common\Models\Helpers\TableListORMHelper;
use Arrow\Shop\Models\Persistent\Parlour;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Arrow\Common\Models\Helpers\Validator;

/**
 * Class PageController
 * @package Arrow\CMS\Controllers
 * @Route("/foreignparlour")
 */
class ForeignParlourController extends BaseController
{
    /**
     * @Route("")
     */
    public function index()
    {
        return [
           true
        ];
    }

    /**
     * @Route("/asyncIndex")
     */
    public function asyncIndex() {
        $criteria = Parlour::get();

        $helper = new TableListORMHelper();

        return $helper->getListData($criteria);
    }

    /**
     * @Route( "/{key}/edit" )
     */
    public function edit(int $key)
    {
        $criteria = Parlour::get()
            ->findByKey( $key );


        return [
            "object" => $criteria,
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

        if($validator->fails()){
            return $validator->response();
        }else{

            $object = Parlour::get()
                ->findByKey( $key )
                ->setValues($data)
                ->save()
            ;

            return [
                "id" => $object->_id()
            ];
        }
    }
}