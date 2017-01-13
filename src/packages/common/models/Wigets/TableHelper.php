<?php
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 25.08.13
 * Time: 18:17
 * To change this template use File | Settings | File Templates.
 */

namespace Arrow\Common;


use Arrow\Controls\api\common\Link;
use Arrow\Controls\API\Forms\Fields\SwitchF;
use Arrow\Controls\API\Forms\Form;
use Arrow\Controls\API\Table\Columns\Editable;
use Arrow\Controls\API\Table\Columns\Template;

class TableHelper{




    public static  function linkColumn(Link $link, $caption =""){
        return Template::_new(function($context) use($link){

            $link->setVars(array_replace_recursive($link->getVars(), ["key" => $context->_id()]));


            return $link->generate();
        }, $caption);
    }



    public static function getByOperation($formId, $object = null){
        $column = Editable::_boolswitch(Product::F_ACTIVE, "Aktywny");
        $column->setEditAction(function($newValue, Editable $widget){

        });
        return;
    }

}