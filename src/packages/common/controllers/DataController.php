<?php

namespace Arrow\Common\Controllers;

use Arrow\Exception;
use Arrow\Media\Element;
use Arrow\Media\ElementConnection;
use Arrow\Media\MediaAPI;
use Arrow\Models\Dispatcher;
use Arrow\Models\IAction;
use Arrow\Models\Project;
use Arrow\ORM\Persistent\Criteria,
    \Arrow\Access\Auth,
    \Arrow\ViewManager, \Arrow\RequestContext, Arrow\Models\Operation,
    Arrow\Router;
use Arrow\Translations\Translations;


/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 04.09.12
 * Time: 14:20
 * To change this template use File | Settings | File Templates.
 */


class DataController extends \Arrow\Models\Controller
{
    private $class = null;
    private $obj = null;
    private $data = null;
    private $parameters = null;
    private $model = null;

    /**
     * @var array
     */
    private $fieldsWithErrors = array();
    private $alerts = array();


    public function validation(IAction $action, RequestContext $request)
    {
        //todo zmienic i wbudowac do orma
        $db = Project::getInstance()->getDB();
        $db->exec("SET sql_mode = 'NO_ENGINE_SUBSTITUTION';");

        $langFields = null;
        if (($request["key"] || $request["id"]) && $request["model"]) {
            //$object = Criteria::query($request["model"])->findByKey($request["key"]);
            if ($request["currentLanguage"] && in_array('Arrow\Package\Common\IMultilangObject', class_implements($request["model"]))) {
                $langFields = call_user_func(array($request["model"], "getMultiLangFields"));
            }
        }

        $formData = \Arrow\Controls\API\Forms\Form::getFormData($request["formControl"]);
        if ($formData["beforeValidation"])
            $formData["beforeValidation"]();


        $proposed = $request[$formData["namespace"]];

        $fieldErrors = array();
        $errors = array();

        $stdError = Translations::translateText("To pole jest wymagane");
        foreach ($formData["required"] as $key => $value) {

            if (is_int($key)) {
                $field = $value;
                $error = $stdError;
            } else {
                $field = $key;
                $error = $value;
            }

            if ($langFields != null && !in_array($field, $langFields))
                continue;
            if ($proposed[$field] === "") {
                $fieldErrors[$field][] = $error;
            }

        }

        foreach ($formData["fieldValidators"] as $field => $validators) {
            foreach ($validators as $validator) {
                $ret = $validator($proposed[$field]);
                if (is_array($ret))
                    $fieldErrors[$field] = array_merge($fieldErrors[$field], $ret);
                elseif (is_string($ret))
                    $fieldErrors[$field][] = $ret;
                elseif (!$ret)
                    $fieldErrors[$field][] = "Some validation problem";
            }
        }

        foreach ($formData["validators"] as $validator) {
            $ret = $validator($proposed);
            if (is_array($ret)) {
                foreach ($ret as $key => $val) {
                    if (is_int($key)) {
                        $errors[] = $val;
                    } else {
                        if (is_array($val))
                            $fieldErrors[$key] = array_merge($fieldErrors[$key], $val);
                        else
                            $fieldErrors[$key][] = $val;
                    }
                }
            } elseif (is_string($ret))
                $errors[] = $ret;
            elseif (!$ret)
                $errors[] = "Some validation problem";
        }


        /*     print "<pre>";
             print_r($formData);
             print_r($proposed);
             print_r($fieldErrors);

             exit();*/

        if (empty($fieldErrors) && empty($errors)) {
            //executing real form action
            //$actions = Router::resolveActions(Router::getActionParameter($formData["action"]));
            //$actions = Router::resolveActions();
            $action = Router::getActionParameter($formData["hidden"]["action"]);
            ob_start();
            Dispatcher::getDefault()->get(str_replace(Router::getBasePath(), "",$formData["hidden"]["action"]))->fetch($request);
            ob_clean();


            $this->json([true]);
        }


        if ($request->isXHR())
            $this->json(["fieldErrors" => $fieldErrors, "errors" => $errors, "namespace" => $formData["namespace"]]);


    }

    private function getRequestValue($field, \Arrow\RequestContext $request)
    {
        $namespace = $field["namespace"];
        if ($namespace == false)
            return $request->getRequest($field["name"]);

        $formData = $request->getRequest($namespace);
        $val = isset($formData[$field["name"]]) ? $formData[$field["name"]] : false;

        if ($field["class"] == "Arrow\\Controls\\FormNumber")
            $val = str_replace(",", ".", $val);

        return $val;
    }

    public function getErrorAsTable($formData)
    {
        $ret = array("errors" => array(), "alerts" => array());

        foreach ($this->fieldsWithErrors as $fieldName => $alert) {
            if (empty($alert)) $alert[] = "Validation of " . $fieldName . " not passed.";

            $ret["errors"][$fieldName] = array("alert" => $alert, "namespace" => $formData["fields"][$fieldName]["namespace"]);
        }
        foreach ($this->alerts as $alert) {
            $ret["alerts"][] = $alert;
        }

        return $ret;
    }

    public function operation(IAction $action, RequestContext $request)
    {
        $this->json(["key" => $this->performElement($request->getRequest())]);
    }

    private function performElement($request)
    {
        if (!array_key_exists('model', $request))
            throw new \Arrow\Exception('Attribute  "model" not found ', 0);
        if (!array_key_exists('action', $request))
            throw new \Arrow\Exception('Attribute  "action" not found ', 0);

        //todo wytestowac czemu strip
        $this->class = str_replace('\\\\', '\\', $request['model']);
        $action = $request['action'];


        $key = false;
        if ($request["key"])
            $key = $request['key'];

        if (isset($request["data"]))
            $this->data = $request["data"];

        if (isset($request["parameters"])) {
            $this->parameters = $request["parameters"];
        }


        //normal object
        if ($key !== false && $key !== "") {
            if (is_array($key)) {
                $this->obj = Criteria::query($this->class)->c("id", $key, Criteria::C_IN)->find();
                foreach ($this->obj as $obj)
                    $obj->setParameters($this->parameters);
            } else {
                $this->obj = Criteria::query($this->class)->findByKey($key);

                $this->obj->setParameters($this->parameters);
                if (empty($this->obj))
                    throw new \Arrow\Exception(array('msg' => "[Action Router] Object {$this->class} {$key} does not exist", 'class' => $this->class, 'key' => $key));
            }
        }

        //preform action routing
        switch ($action) {

            case 'create':
                return $this->create();
                break;
            case 'fileUpload':
                return $this->fileUpload();
                break;
            case 'save':
                if (!is_array($this->obj))
                    return $this->save();
                else {
                    $tmp = $this->obj;
                    $result = array();
                    foreach ($tmp as $obj) {
                        $this->obj = $obj;
                        $result[] = $this->save();
                    }
                    $this->obj = $tmp;
                    return $result;
                }
                break;
            case 'delete':
                return $this->delete();
                break;
            case 'move':
                return $this->move();
                break;
            case 'copy':
                return $this->copy();
            case 'download':
                return $this->download();
                break;
            case 'image_cut':
                return $this->image_cut();
                break;
            default:
                throw new Exception("Action not found");

        }

    }

    //Implementation af allowed actions

    //Action create
    private function create()
    {

        $this->obj = new $this->class($this->data, $this->parameters);
        $this->obj->save();




        $this->json(["key" => $this->obj->getPKey()]);
    }

    //Action SAVE
    private function save()
    {

        $rq = RequestContext::getDefault();

        if ($rq["currentLanguage"]) {
            return $this->saveLangData($this->obj, $this->data, $rq["currentLanguage"]);
        }


        $created = false;

        if ($this->obj !== null) {
            $this->obj->setValues($this->data);
        } else {
            $this->obj = new $this->class($this->data, $this->parameters);
            $created = true;

        }

        if (!empty($this->obj)) {
            $ret = $this->obj->save();
        } else
            throw new \Arrow\Exception("Error while saving object");

        if (isset($rq["_FILES_REMOVE_"])) {
            foreach ($rq["_FILES_REMOVE_"] as $path) {
                $el = Element::get()
                    ->c("path", $path)
                    ->findFirst();
                $el->delete();

            }
        }

        $filesNamespaces = array("_FILES_UPLOAD_SINGLE_", "_FILES_UPLOAD_MULTI_");

        foreach ($filesNamespaces as $nsType) {

            if (isset($_FILES[$nsType])) {

                //changing $_Files array to more readablem form $ar[name][elements]
                $files = array();
                foreach ($_FILES[$nsType]["name"] as $namespace => $el) {
                    foreach ($el as $index => $path) {
                        foreach ($_FILES[$nsType] as $type => $values)
                            $files[$namespace][$index][$type] = $_FILES[$nsType][$type][$namespace][$index];
                    }
                }

                foreach ($files as $namespace => $elements) {
                    foreach ($elements as $data) {
                        if ($nsType == "_FILES_UPLOAD_SINGLE_") {
                            $el = MediaAPI::getObjElements($this->obj, $namespace);
                            foreach ($el as $e)
                                $e->delete();
                        }

                        MediaAPI::setObjectFile($this->obj, $namespace, $data["name"], $data["tmp_name"]);
                    }
                }
            }
        }

        if($created){
            if(isset($_REQUEST["multifiles"])){
                $elements = [];
                foreach( $_REQUEST["multifiles"] as $section ){
                    foreach($section as $el){
                        $elements[] = $el;
                    }
                }
                $con = ElementConnection::get()
                    ->c( "element_id", $elements, Criteria::C_IN  )
                    ->find();
                foreach($con as $c){
                    $c[ElementConnection::F_OBJECT_ID] = $this->obj->getPKey();
                    $c[ElementConnection::F_MODEL] = $this->class;
                    $c->save();
                }
            }
        }


        $this->json(["key" => $this->obj->getPKey()]);
    }

    private function saveLangData($object, $data, $lang)
    {
        Translations::setupLang($lang);
        Translations::saveObjectTranslation($object, $data);
        $this->json(["key" => $this->obj->getPKey()]);
    }


    //Action DELETE
    private function delete()
    {
        if ($this->obj instanceof \Arrow\ORM\DataSet) {
            foreach ($this->obj as $obj)
                $obj->delete();
        } else
            return $this->obj->delete();

        $this->json([true]);

    }


    private function copy()
    {
        throw new \Arrow\Exception("to implement");

    }

    //Action MOVE
    private function move()
    {
        if (isset($this->data["prev"])) {
            if ($this->data["prev"] == -1 && $this->data["next"] == -1)
                return;

            if ($this->data["prev"] != -1) {
                $prev = Criteria::query($this->class)->findByKey($this->data["prev"]);
                $prevSort = $prev["sort"];
                $table = call_user_func(array($this->class, 'getTable'));
                $f = $this->data["field"];
                $q = "update {$table} set {$f}={$f}+1 where {$f}>{$prevSort}";
                Project::getInstance()->getDB()->execute($q);
                $this->obj->setValue($f, $prevSort + 1);
                $this->obj->save();
                return $this->obj->getPKey();
            } elseif ($this->data["next"] != -1) {
                $next = Criteria::query($this->class)->findByKey($this->data["next"]);
                $nextSort = $next["sort"];
                $table = call_user_func(array($this->class, 'getTable'));
                $f = $this->data["field"];
                $q = "update {$table} set {$f}={$f}+1 where {$f}>={$nextSort}";
                Project::getInstance()->getDB()->execute($q);
                $this->obj->setValue($f, $nextSort);
                $this->obj->save();
                return $this->obj->getPKey();

            } else {
                //move nothing
            }

            return $newSort;
        }

        $class = $this->class;
        $fields = $class::getFields();
        //for classes implementing tree node
        if (in_array("parent_id", $fields)) {

            $request = RequestContext::getDefault();

            $dir = $request['direction'];
            if ($dir == 'up') {
                $this->obj->moveUp();
            } else
                $this->obj->moveDown();

            $this->obj->save();

        } else {
            if (isset($this->data["startIndex"]) && isset($this->data["targetIndex"]) && isset($this->data["field"])) {
                $start = $this->data["startIndex"];
                $target = $this->data["targetIndex"];
                $field = $this->data["field"];
            } else {
                throw new \Arrow\Exception('Required field missing for Move action [field]');
            }

            $criteria = new Criteria();
            $criteria->addOrderBy($field, 'asc');
            if (isset($this->data["criteria"]))
                $criteria->fromString($this->data["criteria"]);

            $result = call_user_func(array($object, 'getByCriteria'), $criteria, $object);

            if ($start != $target)
                if ($start > $target)
                    for ($i = $target; $i < $start; $i++) {

                        $tmp = $result[$i][$field];
                        $result[$i][$field] = $result[$i + 1][$field];
                        $result[$i + 1][$field] = $tmp;

                        $result[$i]->save();
                        $result[$i + 1]->save();
                    }
                else
                    for ($i = $target; $i > $start; $i--) {

                        $tmp = $result[$i][$field];
                        $result[$i][$field] = $result[$i - 1][$field];
                        $result[$i - 1][$field] = $tmp;

                        $result[$i]->save();
                        $result[$i - 1]->save();
                    }
        }
    }


    private function download()
    {
        $path = $_SERVER['DOCUMENT_ROOT'] . "/path2file/"; // change the path to fit your websites document structure
        $fullPath = $this->obj['path'];


        if ($fullPath != "/" && file_exists($fullPath)) {
            if ($fd = fopen($fullPath, "r")) {
                $fsize = filesize($fullPath);
                $path_parts = pathinfo($fullPath);
                $ext = strtolower($path_parts["extension"]);
                switch ($ext) {
                    case "pdf":
                        header("Content-type: application/pdf"); // _add here more headers for diff. extensions
                        header("Content-Disposition: attachment; filename=\"" . $path_parts["basename"] . "\""); // use 'attachment' to force a download
                        break;
                    default;
                        header("Content-type: application/octet-stream");
                        header("Content-Disposition: filename=\"" . $path_parts["basename"] . "\"");
                }
                header("Content-length: $fsize");
                header("Cache-control: private"); //use this to open files directly
                while (!feof($fd)) {
                    $buffer = fread($fd, 2048);
                    echo $buffer;
                }
            }
            fclose($fd);
            exit;
        } else {
            \Arrow\Controller::getDefault()->rollBackRequest();
        }
    }


    public function fileUpload()
    {
        foreach ($_FILES as $name => $data) {
            Element::uploadToObject($this->obj, $name, $data["name"], $data["tmp_name"]);
        }

        exit('{"success": true}');
    }

    public function image_cut(){

        $imTransform = new \ImageTransform();
        $imTransform->load($this->obj["path"]);
        file_put_contents("./data/cache/tmp.crop","");
        $imTransform->setTargetFile("./data/cache/tmp.jpg");


        /*
         cutData[x]:1910.6399999999999
        cutData[y]:533.52
        cutData[x2]:3647.9999999999995
        cutData[y2]:1455.0761739130648
        cutData[w]:1737.36
        cutData[h]:921.5561739130649
         */

        $rq = RequestContext::getDefault();
        $cutdata = $rq["cutData"];
        foreach($cutdata as $key => $el )
            $cutdata[$key] = floor($el);

        $imTransform->crop($cutdata["w"], $cutdata["h"], $cutdata["x"],$cutdata["y"] );

        unlink($this->obj["path"]);
        copy("./data/cache/tmp.jpg", $this->obj["path"]);

        $this->obj->createSystemMiniature();
        MediaAPI::refreshImageCache($this->obj);





        exit($this->obj["path"]);
    }


}