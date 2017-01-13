<?php namespace Arrow\Models;

use Arrow\ConfigProvider;
use Arrow\RequestContext;
use Arrow\Router;

/**
 * Arrow template structure
 *
 * @version  1.0
 * @license  GNU GPL
 * @author   Artur Kmera <artur.kmera@arrowplatform.org>
 */
class Dispatcher
{

    private static $classPathResolver;

    public static function setClassPathResolver($resolver){
        self::$classPathResolver = $resolver;
    }

    public function resolvePath($path)
    {
        if ($path[0] == ".") {
            $tmp = explode("/",Router::getActionParameter());
            $tmp[count($tmp)-1] = substr( $path, 2 );
            $path = implode("/",$tmp);

        }
        $path = $path[0] == "/" ? $path :  "/".$path;
        $data = ["package" => 'app', "path" => $path, "shortPath" => $path, "controller" => 'App\\Controllers\\Controller'];
        $packages = Project::getInstance()->getPackages();

        $equateConf = false;



        foreach ($this->configuration["path"] as  $_path => $_data) {

            if(is_string($_path) ) {

                if(strpos($path, $_path) === 1) {
                    $equateConf = $_data;
                    break;

                }

            }else if ($_path["type"] == "prefix" && strpos($path, $_path["path"]) === 0) {
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

            if(is_string($equateConf)) {
                $data["controller"] = $equateConf;

                $file = self::$classPathResolver->findFile($equateConf);
                $xpath = str_replace( [ARROW_DOCUMENTS_ROOT, "composer/../", "/"], ["","",DIRECTORY_SEPARATOR], dirname($file));

                 foreach($packages as $name => $dir){
                    $dir = str_replace("/",DIRECTORY_SEPARATOR, $dir);

                    $pos = strpos($xpath, $dir);
                    if($pos === 0 || $pos === 1){
                        $data["package"] = $name;
                    }
                }
                $data["shortPath"] = trim(str_replace($_path,"", $path), "/");
            }else{

                $data["controller"] = $equateConf["controller"];
                $data["layout"] = $equateConf["layout"];
                if (isset($equateConf["base"]) && $equateConf["base"]) {
                    $data["shortPath"] = str_replace($equateConf["base"], "", $data["path"]);
                }
                if (isset($equateConf["package"]) && $equateConf["package"]) {
                    $data["package"] = $equateConf["package"];
                }
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
        $action = new Action($pathInfo["path"], $pathInfo["shortPath"] , null, $pathInfo["controller"], $pathInfo["package"]);

        self::$actions[$path] = $action;
        return  $action;
    }

    public function findByRewrite( $path ){
        $action = null;


        foreach( $this->configuration["rewrite"] as $_rewrite => $rewrite ){


            if(preg_match_all("/".$_rewrite."/", $path, $regs, PREG_SET_ORDER)){

                $request = RequestContext::getDefault();;
                $c = count($regs[0]);

                if(!is_array($rewrite)) {
                    $action = $this->get($rewrite, true);
                    break;
                }

                for($i=1;$i<$c;$i++ ){
                    $request->addParameter($rewrite["params"][$i-1], $regs[0][$i]);
                }
                for($i=$c-1;$i<count($rewrite["params"]);$i++ ){
                    if( is_array($rewrite["params"][$i])) {
                        $request->addParameter(key($rewrite["params"][$i]), reset($rewrite["params"][$i]));
                    }else
                        $request->addParameter($rewrite["params"][$i], null);
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
        $this->configuration = ConfigProvider::get("route");
        $this->configuration["path"] = ConfigProvider::arrayFlat($this->configuration["path"], '');
    }

}

?>
