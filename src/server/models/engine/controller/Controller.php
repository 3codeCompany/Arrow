<?php
namespace Arrow\Models;
use Arrow\Access\Models\AccessAPI;
use Arrow\Access\Models\Auth;
use Arrow\RequestContext;
use Arrow\Router;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 04.09.12
 * Time: 13:21
 * To change this template use File | Settings | File Templates.
 */
//todo - tutaj wszystko ok ale zrobic porzadek z singletonami trzeba

abstract class Controller implements IController
{

    /**
     * @var Action
     */
    protected $action;

    /**
     * @var RequestContext
     */
    public $request;

    protected static $instance;
    const CAUGHT_NOT_FOUND = 1;

    protected $appParams;

    protected function __construct()
    {

    }

    final private function __clone()
    {
    }


    /**
     * @return Action
     */
    public function getPackage()
    {
        $path = str_replace( ARROW_DOCUMENTS_ROOT, "", dirname((new \ReflectionObject($this))->getFileName()));
        foreach(Project::getInstance()->getPackages() as $name => $dir){
            $dir = str_replace("/",DIRECTORY_SEPARATOR, $dir);
            $pos = strpos($path, $dir);
            if($pos === 0 || $pos === 1){
                return $name;
            }
        }
    }


    public function setAppParam($name, $default){
        if(!isset($_SESSION["arrow.app.params"][$name]))
            $_SESSION["arrow.app.params"][$name] = $default;

        $rq = RequestContext::getDefault();

        if($rq[$name]){
            $_SESSION["arrow.app.params"][$name] = $rq[$name];
        }
        return $_SESSION["arrow.app.params"][$name];

    }

    public function setAppParamValue( $name, $val ){
        $_SESSION["arrow.app.params"][$name] = $val;
    }

    public function getAppParam( $name, $default = null ){
        if(!isset($_SESSION["arrow.app.params"][$name]))
            return $default;
        return $_SESSION["arrow.app.params"][$name];
    }

    final protected function back(  ){

        if(RequestContext::getDefault()->isXHR()){
            return $this->json([true]);
        }

        if(isset($_SERVER["HTTP_REFERER"])){
            header("Location: ".$_SERVER["HTTP_REFERER"]);
        }else{
            header("Location: ".Router::getBasePath());
        }
        exit();
    }
    final protected function json(  $data = [] ){

        header("Content-type: text/json");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        $coded = json_encode($data);
        $rq = RequestContext::getDefault();
        if($rq["jsonpCallback"] ){
            echo $rq["jsonpCallback"]."(".$coded.")";
        }else{
            header('Content-Type: text/html');
            echo $coded;
        }
        exit();
    }


    /**
     * @return static
     */
    final public static function getInstance( )
    {
        static $aoInstance = array();

        $calledClassName = get_called_class();

        if (!isset ($aoInstance[$calledClassName])) {
            $aoInstance[$calledClassName] = new $calledClassName();
            $aoInstance[$calledClassName]->action = Router::getDefault()->getAction();
            $aoInstance[$calledClassName]->request = RequestContext::getDefault();
        }

        return $aoInstance[$calledClassName];
    }




    public function eventRunBeforeAction(Action $action)
    {
    }
    public function eventRunAfterAction(Action $action )
    {
    }

    public function viewBeforeCompileEvent( Action $view ){}

    public function viewAfterCompileEvent( Action $view ){}

    public function notFound(Action $action = null, RequestContext $request  = null)
    {

        $user = Auth::getDefault()->getUser();

        if($user && $user->isInGroup(AccessAPI::GROUP_DEVELOPERS)){

            $name = str_replace(DIRECTORY_SEPARATOR, "_",trim( $action->getShortPath(), "/"));
            throw new \Arrow\Exception(
                array(
                    "msg"        => "Undefined controller action  '$name' in controller " . get_class($this),
                    "view"       => $action->getPath(),
                    "package"    => $action->getPackage(),
                    "controler"    => get_class($this),
                    "actionCode" => "public function $name(){}"
                ));


            exit();
        }


        header("HTTP/1.0 404 Not Found");
        print "<h1>404 Not found</h1>";

        print "Contact with administrator ";
        if($action){
            print "[ {$action->getPath()} ]";
        }
        exit("");

    }


    public function __call($name, $arguments)
    {




        if ( isset($arguments[0]) && $arguments[0] instanceof \Arrow\Models\Action) {
            /** @var VievD $viewD */
            $viewD = $this->action;
            //$result = $this->notFound($viewD);
            if (true || $result !== self::CAUGHT_NOT_FOUND ) {
                throw new \Arrow\Exception(
                    array(
                         "msg"        => "Undefined controller action  '$name' in controller " . get_class($this),
                         "view"       => $viewD->getPath(),
                         "package"    => $viewD->getPackage(),
                         "actionCode" => "public function $name( View \$view, RequestContext \$request ){}"
                    ));
            }
        } else {
            throw new \Arrow\Exception(
                array(
                     "msg" => "Undefined controller method '$name' in controller " . get_class($this),
                ));
        }
    }


}
