<?php
namespace Arrow;
use Arrow\Models\Action;
use Arrow\Models\Dispatcher;
use Arrow\Models\IAction;

/**
 * Router
 *
 * @version  1.0
 * @license  GNU GPL
 * @author   Artur Kmera <artur.kmera@arrowplatform.org>
 * @todo     Rozwinoc o ciastka i pliki, dodac wykrywanie typu wywołania
 */
class Router extends \Arrow\Object
{

    public static $INDEX_FILE = ""; //"index.php";


    /**
     * Template to display
     *
     * @var Action
     */
    private $action;

    /**
     * Call type
     *
     * @var integer
     */
    private $callType;


    /**
     * Router instance
     *
     * @car Router
     */
    private static $oInstance = null;

    private static $basePath = "";

    private static $packageSeparator = ",-";
    private static $actionsSeparator = ",";

    private static $actionName = null;
    private static $actions = array();

    public static function getActionName()
    {
        return self::$actionName;
    }

    public function getAction(){
        return Dispatcher::getDefault()->get(self::getActionName());
    }

    /**
     * Singleton
     *
     * @return Router
     */
    public static function getDefault()
    {
        if (self::$oInstance == null) {
            self::$oInstance = new Router(RequestContext::getDefault());
        }
        return self::$oInstance;
    }

    public function __construct(RequestContext $request)
    {

    }

    public static function generateLinkFromObject($obj)
    {
        return self::getBasePath() . ltrim($obj->getPath(), "/");
    }

    public static function link( $path, array $params = null ){
        if( $path[0] == "?"){
            $path = ViewManager::getCurrentView()->get()->getPath().$path;
        }elseif($path[0] == "."){

            $tmp = explode(DIRECTORY_SEPARATOR, ViewManager::getCurrentView()->get()->getPath());
            $tmp[count($tmp)-1] = substr( $path, 2 );
            unset($tmp[0]);
            $path = implode("/",$tmp);
        }

        $url = str_replace("//","/",self::$basePath.$path);

        if($params){
            $url.="?". \http_build_query( $params );
        }

        return $url;


        //return (self::$basePath=="/"?"":self::$basePath).$path;
    }

    public static function generateLink($type, $path)
    {
        return self::link($path);
    }

    public static function getActionParameter($url = false)
    {

        if ($url == false) {
            if (isset ($_SERVER["REQUEST_URI"])) {
                $url = $_SERVER["REQUEST_URI"];
            } else {
                return null;
            }
        }



        //self::$basePath = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], "/")+1);

        $p = parse_url(urldecode($url));
        $action = $p["path"];
        $action = str_replace(array("index.php", self::$packageSeparator), array("", "::"), $action);
        if (self::$basePath != "/") {
            $action = str_replace(array(self::$basePath), array(""), $action);
        } else {
            $action = substr($action, 1);
        }

        $action = ltrim($action, "/");



        return $action;
    }

    /**
     * @param $actionParameter
     * @return \Arrow\Models\IAction[]
     * @throws Exception|\Exception
     */
    public static function resolveActions($actionParameter)
    {
        return \Arrow\Models\Dispatcher::getDefault()->get($actionParameter);
    }

    public static function setupAction()
    {

        if (Controller::isInCLIMode()) {
            self::$basePath = "";
        } else {
            self::$basePath = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], "/") + 1);
        }


        if (!isset($_SERVER["REQUEST_URI"])) {
            return null;
        }

        $p = parse_url(urldecode("/".ltrim($_SERVER["REQUEST_URI"], "/" )));

        if (self::$basePath == "/")
            $action = str_replace( "index.php", "" , $p["path"]);
        else
            $action = str_replace([self::$basePath, "index.php"], ["", ""], $p["path"]);

        $action = ltrim($action, "/");

        if (empty($action))
            $action = "index";

        self::$actionName = $action;
    }

    public static function getBasePath()
    {
        return self::$basePath;
    }


    /**
     * Return requested template
     *
     * @return \Arrow\Models\View
     */
    public function get()
    {
        return $this->action;
    }


    public function notFound(IAction $action){

        $action->getController()->notFound($action, RequestContext::getDefault());
        exit();
    }

    public function process()
    {
        $dispatcher = \Arrow\Models\Dispatcher::getDefault();
        if (empty(self::$actionName)) {
            $path = \Arrow\Controller::$project->getSetting("application.view.default.view");
            $this->action = $dispatcher->get($path);
        } else {

            $this->action = $dispatcher->get(self::$actionName);
        }

        if(!$this->action->exists()){
            $this->notFound($this->action);
            return;
        }

        echo $this->action->fetch();

    }

}