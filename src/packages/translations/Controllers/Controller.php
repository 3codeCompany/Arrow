<?php

namespace Arrow\Translations\Controllers;


use App\Controllers\BaseController;
use Arrow\Common\Layouts\ReactComponentLayout;
<<<<<<< HEAD
use Arrow\Controls\API\Forms\Validator;
use Arrow\Controls\Helpers\TableListORMHelper;
=======
use Arrow\Common\Models\Helpers\Validator;
use Arrow\Common\Models\Helpers\TableListORMHelper;
>>>>>>> 48b53524a967b453047c1ed0b071d6c459a0526b
use Arrow\Translations\Models\Language;

class Controller extends BaseController
{

    function Language_index()
    {
        $this->action->setLayout(new ReactComponentLayout());
    }

    public function Language_list()
    {
        $ctit = Language::get();
        $helper = new TableListORMHelper();

        $helper->addDefaultOrder(Language::F_NAME);
        $this->json($helper->getListData($ctit));
    }

    public function Language_get()
    {
        $data = Language::get()
            ->findByKey($this->request["key"]);
        $this->json($data);
    }


    public function Language_save(){

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

    public function Language_Language_delete(){
        $data = Language::get()
            ->findByKey($this->request["key"]);
        $data->delete();
        $this->json([true]);
    }

}
