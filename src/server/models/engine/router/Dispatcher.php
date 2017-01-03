<?php namespace Arrow\Models;

use Arrow\RequestContext;
use Arrow\Router;

/**
 * Arrow template structure
 *
 * @version  1.0
 * @license  GNU GPL
 * @author   Artur Kmera <artur.kmera@arrowplatform.org>
 */
class Dispatcher implements \Arrow\ICacheable
{

    public function resolvePath($path)
    {
        if ($path[0] == ".") {
            $tmp = explode("/",Router::getActionParameter());
            $tmp[count($tmp)-1] = substr( $path, 2 );
            $path = implode("/",$tmp);

        }
        $path = $path[0] == "/" ? $path :  "/".$path;
        $data = ["package" => 'application', "path" => $path, "shortPath" => $path, "controller" => 'App\\Controllers\\Controller'];

        $equateConf = false;

        foreach ($this->configuration["packages"]["application"]["paths"] as $_path) {

            if ($_path["type"] == "prefix" && strpos($path, $_path["path"]) === 0) {
                $equateConf = $_path;
                break;
            } elseif ($_path["type"] == "path" && strpos($path, $_path["path"]) === 0) {

                $tmp = str_replace($_path["path"], "", $path);
                $tmp = explode("/", $tmp);
                $controllerName = $tmp[0];
                $controller = str_replace("*", $controllerName, $_path["controller"]);
                unset($tmp[0]);
                $path = implode("/", $tmp);
                $equateConf = [
                    "controller" => $controller,
                    "path" => $path,
                    "layout" => "",
                    "package" => "",
                    "base" => $_path["base"] .  $controllerName
                ];
                break;
            } elseif ($_path["type"] == "regex" && preg_match($_path["path"], $path)) {
                $equateConf = $_path;
                break;
            } elseif ($_path["type"] == "equal" && $_path["path"] == $path) {
                $equateConf = $_path;
                break;
            }
        }

        if ($equateConf !== false) {

            $data["controller"] = $equateConf["controller"];
            $data["layout"] = $equateConf["layout"];
            if(isset($equateConf["base"]) && $equateConf["base"]){
                $data["shortPath"] = str_replace($equateConf["base"],"",$data["path"]);
            }
            if(isset($equateConf["package"]) && $equateConf["package"]){
                $data["package"] = $equateConf["package"];
            }
        }

        return $data;
    }

    private static $actions = [];
    public function get($path, $skipRewriteTest = false)
    {

        if(isset(self::$actions[$path])){
            return self::$actions[$path];
        }

        if(!$skipRewriteTest) {
            $rewriteTest = $this->findByRewrite($path);
            if ($rewriteTest)
                return $rewriteTest;
        }





        $pathInfo = $this->resolvePath($path);
        $action = new Action($pathInfo["path"], null, $pathInfo["controller"], $pathInfo["package"]);
        $action->setShortPath( $pathInfo["shortPath"] );
        self::$actions[$path] = $action;
        return  $action;
    }

    public function findByRewrite( $path ){
        $action = null;

        foreach( $this->configuration["rewrites"] as $rewrite ){

            if(preg_match_all("/".$rewrite["queryString"]."/", $path, $regs, PREG_SET_ORDER)){
                $request = RequestContext::getDefault();;
                $c = count($regs[0]);

                for($i=1;$i<$c;$i++ ){
                    $request->addParameter($rewrite["params"][$i-1], $regs[0][$i]);
                }
                for($i=$c-1;$i<count($rewrite["params"]);$i++ ){
                    $request->addParameter($rewrite["params"][$i], $rewrite["paramsValues"][$i]);
                }

                $action = $this->get($rewrite["path"], true);

                break;
            }
        }
        return $action;
    }


    /**
     * Configuration from cache provider
     *
     * @var Array
     */
    private $configuration;

    /**
     * Templates Structure instance
     *
     * @var Dispatcher
     */
    private static $selfInstance = null;


    /**
     * Singleton !NO_REMOTE
     *
     * @return Dispatcher Default instance
     */
    public static function getDefault()
    {
        if (self::$selfInstance == null) {
            self :: $selfInstance = new Dispatcher();
        }
        return self :: $selfInstance;
    }

    /**
     * Constructor !NO_REMOTE
     *
     * @param Integer $projectId Project Id.
     */
    private function __construct()
    {
        $this->configuration = \Arrow\CacheProvider::getFileCache($this, array($this, "getFilesForCache"), array("file_prefix" => "route_structure"));

    }

    public function getFilesForCache()
    {
        $conf = array();
        foreach (\Arrow\Controller::$project->getPackages() as $package) {
            if (!file_exists($package["dir"] . "/conf/route.xml")) {
                continue;
            }
            $conf[] = $package["dir"] . "/conf/route.xml";
        }
        return $conf;
    }

    /**
     * \Arrow\ICacheable implementation
     *
     * @param Array $params
     *
     * @return Array
     */
    public function generateCache($params)
    {
        $tmp = array();
        foreach (\Arrow\Controller::$project->getPackages() as $package) {
            if (!file_exists($package["dir"] . "/conf/route.xml")) {
                continue;
            }
            $actions = $this->parseActionsDocument(simplexml_load_file($package["dir"] . "/conf/route.xml"), $package["namespace"]);
            $tmp = array_merge_recursive ($tmp, $actions);
        }
        return $tmp;
    }

    private function parseActionsDocument($confDocument, $idPackage = "")
    {
        $defaultController = "" . $confDocument["controller"];
        $viewsController = "" . $confDocument->views["controller"];
        $operationsController = "" . $confDocument->operations["controller"];
        $parsed = array();

        $parsed["packages"][$idPackage]["paths"] = array();
        if (isset($confDocument->path)) {

            foreach ($confDocument->path as $path) {
                $parsed["packages"][$idPackage]["paths"][] = array(
                    "type" => (string)$path["type"],
                    "base" => (string)$path["base"],
                    "path" => (string)$path["path"],
                    "controller" => (string)$path["controller"],
                    "layout" => (string)$path["layout"],
                    "package" => (string)$path["package"]
                );
            }

            $parsed["packages"][$idPackage]["paths"] = $parsed["packages"][$idPackage]["paths"];
        }
        if(!isset($parsed["rewrites"]))
            $parsed["rewrites"] = array();
        if (isset($confDocument->rewrite)) {
            foreach ($confDocument->rewrite as $path) {
                $params = [];
                $paramsValues = [];
                foreach ( $path->param as $param) {
                    $params[] = (string) $param["name"];
                    $paramsValues[] = isset($param["value"])?(string)$param["value"]:null;
                }

                $parsed["rewrites"][] = array(
                    "queryString" => (string)$path["queryString"],
                    "path" => (string)$path["path"],
                    "params" => $params,
                    "paramsValues" => $paramsValues,
                );
            }
        }

        if ($defaultController)
            $parsed["packages"][$idPackage]["defaultControllers"] = $defaultController;


        return $parsed;
    }



    public function getRouteStructure($package)
    {
        return $this->configuration["packages"][$package];
    }


}

?>
