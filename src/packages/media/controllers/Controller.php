<?php

namespace Arrow\Media;

use Arrow\Models\Operation;
use Arrow\ORM\Persistent\Criteria,
    \Arrow\Access\Models\Auth,
    \Arrow\ViewManager, \Arrow\RequestContext, Arrow\Models\View;
use Arrow\Common\EmptyLayout;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 04.09.12
 * Time: 14:20
 * To change this template use File | Settings | File Templates.
 */

class Controller extends \Arrow\Models\Controller
{
    public function actionRun($action, Action $view, RequestContext $request, $packageNamespace)
    {

    }

    public function dashboard_main(Action $view, RequestContext $request, $package)
    {
    }

    public function browser(Action $view, RequestContext $request, $package)
    {
        $criteria = new Criteria(Element::getClass());
        $criteria->addOrderBy("sort", Criteria::O_ASC);
        $criteria->addCondition("name", "", "!=");
        $view->assign("criteria", $criteria);

    }

    public function folder_editFolder(Action $view, RequestContext $request, $package)
    {
        $id = $request->getRequest("id");
        if ($id !== false) {
            $view->assign("folder", Criteria::query(Folder::getClass())->findByKey($id));
        } else {
            $view->assign("folder", false);
        }
    }

    public function plugins_objectPhotos(Action $view, RequestContext $request, $package)
    {
        $rq = RequestContext::getDefault();
        $view->assign("name", $rq["name"] ? $rq["name"] : "noname");

        $object = Criteria::query($rq["model"])->findByKey($rq["object_id"]);
        $view->assign("object", $object);
    }

    public function plugins_upload_filesToObject( Action $view, RequestContext $request ){
        $view->setLayout(new EmptyLayout());
    }

    public function operations_addFileToObject( Operation $operation, RequestContext $request){

        $element = new Element(array(), array("connection" => array(
            ElementConnection::F_MODEL => $request["model"],
            "key" => $request["key"],
            ElementConnection::F_NAME => $request["name"],
            "direct" => 1
        )));
        $element->save();
        return;
    }

    public function file_delete( Operation $operation, RequestContext $request){
        Criteria::query(Element::getClass())->findByKey($request["key"])->delete();
    }

    public function plugins_manageObjectFiles( Action $view, RequestContext $request ){
        $view->setLayout(new EmptyLayout());

        $object = Criteria::query($request["model"])->findByKey($request["key"]);

        MediaAPI::prepareMedia(array($object));
        $view->assign("object", $object);
        $view->assign("name", $request["name"]);
    }





}