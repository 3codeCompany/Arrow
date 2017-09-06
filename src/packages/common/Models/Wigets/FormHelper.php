<?php
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 25.08.13
 * Time: 18:17
 * To change this template use File | Settings | File Templates.
 */

namespace Arrow\Common;


use Arrow\Controls\API\Forms\Fields\Hidden;
use Arrow\Controls\API\Forms\FieldsList;
use Arrow\Controls\API\Forms\Form;
use Arrow\Controls\API\Forms\FormBuilder;
use Arrow\Controls\api\Layout\schemas\Bootstrap;
use Arrow\Router;

class FormHelper{

    /**
     * @param $formId
     * @return Form
     */
    public static function getByStandardRouter($formId, $class = null , $object = null){
        $form = Form::_new($formId,$object)
            ->addHidden("action", Router::link("common/data/operation" ))
            ->setAction( Router::link("common/data/validation"))
            ->setNamespace("data");

        if($class){
            $form
                ->addHiddenField("model", $class )
                ->addHiddenField("action", 'save' )
                ->addHiddenField("key", $object?$object->getPKey():"" );
        }

        return $form;
    }

    /**
     * @param $formId
     * @param $class
     * @param null $object
     * @return FormBuilder
     */
    public static function getBuilderByStandardRouter($formId, $class , $object = null){
        $form = self::getByStandardRouter($formId, $class , $object);
        $form->setValues($object);

        $fieldList = FieldsList::create()
            ->addField(null,Hidden::_new("model",$class)->setNamespace(""))
            ->addField(null,Hidden::_new("action","save")->setNamespace(""))
            ->addField(null,Hidden::_new("key",$object?$object->getPKey():"")->setNamespace(""));


        return FormBuilder::_new($form,$fieldList, new Bootstrap());
    }


    public static function getByOperation($formId, $object = null){

    }

}