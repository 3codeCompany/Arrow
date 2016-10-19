<?php
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 26.07.13
 * Time: 01:02
 * To change this template use File | Settings | File Templates.
 */

namespace Arrow\Package\Common;


use Arrow\Controls\api\common\Icons;
use Arrow\Controls\API\Components\Toolbar;
use Arrow\Controls\API\Forms\AbstractFormBuilderField;
use Arrow\Controls\API\Forms\Fields\File;
use Arrow\Controls\API\Forms\Fields\Hidden;
use Arrow\Controls\API\Forms\Fields\Select;
use Arrow\Controls\API\Forms\Fields\Text;
use Arrow\Controls\API\Forms\Fields\Textarea;
use Arrow\Controls\API\Forms\Fields\Wyswig;
use Arrow\Controls\API\Forms\FieldsList;
use Arrow\Controls\API\Forms\Form;
use Arrow\Controls\API\Forms\FormBuilder;
use Arrow\Controls\api\Layout\LayoutBuilder;
use Arrow\Controls\api\Layout\schemas\Bootstrap;
use Arrow\Controls\api\SerenityJS;
use Arrow\Models\Logger\ConsoleStream;
use Arrow\Models\Logger\Logger;
use Arrow\ORM\Criteria;
use Arrow\ORM\PersistentObject;
use Arrow\Package\Access\AccessAPI;
use Arrow\Package\Application\Language;
use Arrow\Package\Common\IMultilangObject;
use Arrow\Package\Langs\Lang;
use Arrow\Package\Media\MediaAPI;
use Arrow\RequestContext;
use Arrow\Router;

class PopupFormBuilder
{

    /**
     * @var Form
     */
    protected $form;

    /**
     * @var LayoutBuilder
     */
    protected $fieldsList;

    /**
     * @var PersistentObject
     */
    protected $object;

    protected $titleAdd = "Dodaj";
    protected $titleEdit = "Edytuj";
    public  $languagesOn = false;
    public $languagesForceDisable = false;
    public $disableCloseButtons = false;

    public static $usedLangs = [];

    protected $model;
    public $currLang = "pl";

    protected $extraBuilders = [];

    /**
     * @param Form $form
     * @param FieldsList $fieldsList
     * @param null $object
     * @return PopupFormBuilder
     */
    public static function _new(Form $form, LayoutBuilder $fieldsList, $model, $object = null)
    {
        return new PopupFormBuilder($form, $fieldsList, $model, $object);

    }

    public function addFormBuilder( FormBuilder $builder, $prepareBuilder = false){
        $this->extraBuilders[] = $builder;
    }

    /**
     * @param Form $form
     * @param FieldsList $fieldsList
     * @param null $object
     */
    function __construct(Form $form, LayoutBuilder $fieldsList, $model, $object = null)
    {

        $operation = Router::link("common/data/operation");
        $validator = Router::link("common/data/validation");
        $action = $form->getAction();
        if( $action && $action != $validator)
            $operation = $action;

        if(empty($action))
            $action = $validator;

        $form
            ->addHidden("action", $operation )
            ->setAction($action)
            ->setNamespace("data")
            ->on(Form::EVENT_SUCCESS, SerenityJS::back())
            //->setJSOnSuccess("popupOnSuccess")
            //->setJsOnError("popupOnError")
            ->setValuesSetter(function (AbstractFormBuilderField $field, $values)use($form) {
                if (!$values)
                    return;

                if ($field->getNamespace() === false) {
                    if ($field->getName() == "key")
                        $field->setValue($values->getPKey());


                }elseif ($field instanceof File) {

                    MediaAPI::prepareMedia(array($values));
                    $media = $values->getParameter("media");
                    $name = str_replace("][","",$field->getName());
                    if (isset($media[$name]))
                        $field->setValue($media[$name][0]["path"]);
                } elseif(isset($values[$field->getName()]) && $field->getValue(true) === null){
                    $field->setValue($values[$field->getName()]);
                }
            });

        $form
            ->addHiddenField("model", $model)
            ->addHiddenField("action", "save")
            ->addHiddenField("key", $object?$object->getPKey():"");

        $this->fieldsList = $fieldsList;
        $this->form = $form;
        $this->object = $object;
        $this->builder = FormBuilder::_new($form, $fieldsList, new Bootstrap());
        $this->model = $model;


        //setupFiles
        $fields = $fieldsList->getElemetns();
        foreach ($fields as $entry) {
            if (!isset($entry[0]))
                continue;
            $field = $entry[0];
            if ($field instanceof File) {
                $field->setNamespace("_FILES_UPLOAD_SINGLE_");
                $field->setName( $field->getName()."][" );

            }
        }


        $request = RequestContext::getDefault();
        if ($request["currLang"])
            $this->currLang = $request["currLang"];

        //Logger::get('console', new ConsoleStream())->log("log");
        if ( $object && $object instanceof IMultilangObject && class_exists('\Arrow\Package\Application\Language') ) {

            $this->languagesOn = true;
            if ($this->currLang != "pl") {
                $translate = $object::getMultiLangFields();
                $fields = $fieldsList->getElemetns();
                foreach ($fields as $entry) {
                    if (!isset($entry[0]))
                        continue;
                    $field = $entry[0];
                    if ($field instanceof AbstractFormBuilderField) {

                        if ((!$field instanceof Hidden)) {
                            if (!in_array($field->getName(), $translate)) {
                                $fieldsList->removeField($field);
                            }
                        }
                    }
                }
                $fieldsList->addField("", Hidden::_new("currentLanguage", $this->currLang)->setNamespace(false));

                $currLang = $this->currLang;
                $this->builder->addFieldDecorator(function ($code, AbstractFormBuilderField $field) use ($currLang, $object) {
                    if ($field instanceof Text || $field instanceof Textarea) {
                        $href = ' <a href="" class="lang-translate" data-value="' . $field->getValue() . '" data-to="' . $currLang . '"><i class="icon-comments-alt"></i> </a>';
                        $field->setPlaceholder($field->getValue());

                        Translations::setupLang($currLang);
                        Translations::translateObject($object);

                        $field->setValue($object[$field->getName()]);

                        return $field->generate() . $href;
                    }
                    if($field instanceof Wyswig){
                        $href = ' <a href="" class="lang-translate-large" data-to="' . $currLang . '"><i class="icon-comments-alt"></i> Zobacz tłumaczenie</a>';
                        return $href.$code;
                    }
                    return $code;
                });
            }
        }
    }

    /**
     * @param string $title
     */
    public function setTitles($titleAdd, $titleEdit)
    {
        $this->titleAdd = $titleAdd;
        $this->titleEdit = $titleEdit;
        return $this;
    }


    protected function generateLangSwitch()
    {
        if(class_exists('\Arrow\Package\Application\Language')){

            $langs = [];
            if(!empty(self::$usedLangs))
                $tmp = \Arrow\Package\Application\Language::get()
                    ->c(Language::F_CODE, self::$usedLangs, Criteria::C_IN)
                    ->find();
            else{
                $langs["pl"] = "Polski";
                $tmp = \Arrow\Package\Application\Language::get()->find();
            }



            foreach($tmp as $l){
                $langs[$l[Language::F_CODE]] = $l[Language::F_NAME];
            }

            $select = Select::_new("lang",$langs, "pl")
                ->setValue($this->currLang)
                ->addCSSClass("switch-lang ")
                ->addHTMLAttribute('data-url', RequestContext::getCurrentUrl());

            return $select->generate();
        }
        return [];
    }

    //todo zmienic na standard
    protected function generateStandardMenu($addText, $editText, $obj, $objNameField = "name", $editProtection = false)
    {
        if ($obj) {
            if ( !$editText && $objNameField && isset($obj[$objNameField]))
                $editText = $obj[$objNameField];
            else
                $editText = $this->titleEdit;
            //$obj->getFriendlyName();
        }


        if ($obj)
            $str = $editText . " ";
        else
            $str = $addText;

        $toolbar = Toolbar::_new($str,[]);


        if ($editProtection && $obj)
            $toolbar->add('<a class="edit-protection" href="#" ><i class="icon-ok-sign"></i>'.Translations::translateText("Kliknij aby edytować").'</a>');

        if( AccessAPI::checkAccess("action", "change", $this->model) ){
            $submit = $this->form->getSubmit('<i class=" fa fa-check"></i> '.Translations::translateText("Zapisz"))->clearCSSClasses();
            $toolbar->add($submit);
            if(!$this->disableCloseButtons && false)
                $toolbar->add('<a class=" btn btn-default" style="padding:6px; !important" onclick="'."Serenity.get( '{$this->form->getId()}' ).on( '".Form::EVENT_SUCCESS."', Page.closeLastDialog ).submit()".'" ><i class="fa fa-arrow-circle-o-right"></i> '.Translations::translateText("Zatwierdź i zamknij").'</a>');

        }
        if(!$this->disableCloseButtons)
            $toolbar->add('<a class="cancel btn btn-default" style="padding:6px; !important"  onclick="'.SerenityJS::back().' return false;"  ><i class="fa fa-sign-out"></i> '.Translations::translateText("Anuluj").'</a>');


        if ($this->languagesOn && !$this->languagesForceDisable) {
            $toolbar->add( Toolbar::text( Translations::translateText('Język').' ',Icons::FLAG) )
                ->add($this->generateLangSwitch());

        }elseif( in_array( "IMultilangObject", class_implements($this->model))) {
            $toolbar->add('<div style="display: inline-block; margin-left: 20px;"><i class="icon-flag"></i> Edycja języków dostępna po zatwierdzeniu wersji polskiej</div>');
        }

        return $toolbar->setId("top-nav")->generate();
    }


    public function generate()
    {
        $str = '<div class="page page-table ng-scope"><div class=" container" style="  width:100%;"><div class="panel panel-primary ">
                <div class="panel-heading"><strong><span class="fa "></span> '.$this->titleAdd.'</strong></div>
                <div class="panel-body"><div class="row  "><div class="col-md-12 ">';

        //$str .= '' . $this->generateStandardMenu("", $this->titleEdit, $this->object) . '';
        if ($this->languagesOn) {
            //$str .= '<div style="display: inline-block;; margin-top: -35px; margin-right: 20px;"><i class="icon-flag"></i> ' . $this->generateLangSwitch() . '</div>';

            if ($this->currLang != "pl") {
                $lang = \Arrow\Package\Application\Language::get()->c("code", $this->currLang)->findFirst();
                $str .= '<div class="alert alert-info" style="margin: 0 5px;"><i class="icon-warning-sign"></i> '.Translations::translateText('Obecnie edytujesz język').': <b>' . $lang["name"] . " [" . $lang["code"] . ']</b></div>';

            }
        }
        $str .= '<div style="clear:both; padding: 0 10px; width: 90%;margin-top:5px;">' .$this->builder->generate();
        foreach($this->extraBuilders as $b)
            $str.= $b->generate();

        $str .= '<br />' . $this->generateStandardMenu("", $this->titleEdit, $this->object) . '';

        $str.= '</div></div></div></div></div></div></div>';
        return $str;
    }

    function __toString()
    {
        return $this->generate();
    }


}





