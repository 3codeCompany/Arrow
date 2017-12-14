<?php

namespace Arrow\Translations\Controllers;


use App\Controllers\BaseController;
use Arrow\Common\Layouts\ReactComponentLayout;
use Arrow\Common\Models\Helpers\Validator;
use Arrow\Common\Models\Helpers\TableListORMHelper;
use Arrow\Translations\Models\Language;

class Languages extends BaseController
{

    function index()
    {
        $this->action->setLayout(new ReactComponentLayout());
    }

    public function list()
    {
        $ctit = Language::get();
        $helper = new TableListORMHelper();

        $helper->addDefaultOrder(Language::F_NAME);
        $this->json($helper->getListData($ctit));
    }

    public function get()
    {
        $data = Language::get()
            ->findByKey($this->request["key"]);
        $this->json($data);
    }


    public function save(){

        $data = $this->request['data'];
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

        $this->json([$obj->_id()]);
    }

    public function delete(){
        $data = Language::get()
            ->findByKey($this->request["key"]);
        $data->delete();
        $this->json([true]);
    }

}
