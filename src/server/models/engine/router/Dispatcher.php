<?php namespace Arrow\Models;

use Arrow\ConfigProvider;
use Arrow\Exception;
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

    public static function setClassPathResolver($resolver)
    {
        self::$classPathResolver = $resolver;
    }

    public function resolvePath($path)
    {

        


        if ($path[0] == ".") {
            $tmp = explode("/", Router::getActionParameter());
            $tmp[count($tmp) - 1] = substr($path, 2);
            $path = implode("/", $tmp);

        }
        $path = $path[0] == "/" ? $path : "/" . $path;
        $data = ["package" => 'app', "path" => $path, "shortPath" => $path, "controller" => 'App\\Controllers\\Controller'];
        $packages = Project::getInstance()->getPackages();

        $equateConf = false;


        foreach ($this->configuration["path"] as $routeConfigPath => $routeConfig) {


            if (strpos($path, $routeConfigPath) === 1) {
                $equateConf = $routeConfig;
                break;
            } elseif (strpos($routeConfigPath, "*") !== false) {
                $pattern = "/" . str_replace("*", "(.*?)", str_replace("/", "\\/", $routeConfigPath)) . "/";

                if (preg_match_all($pattern, $path, $matches)) {
                    $equateConf = $routeConfig;
                    /*  print_r($matches);
                      print $pattern."\n".$path.PHP_EOL;
                  exit();*/
                }


            }


            //@todo rozwinąć routing
            /*if(is_string($routeConfigPath) ) {



            }else if ($routeConfigPath["type"] == "prefix" && strpos($path, $routeConfigPath["path"]) === 0) {
                $equateConf = $routeConfigPath;
                break;
            } elseif ($routeConfigPath["type"] == "path" && strpos($path, $routeConfigPath["path"]) === 0) {

                $tmp = str_replace($routeConfigPath["path"], "", $path);
                $tmp = explode("/", $tmp);
                $controllerName = $tmp[0];
                $controller = str_replace("*", $controllerName, $routeConfigPath["controller"]);
                unset($tmp[0]);
                $path = implode("/", $tmp);
                $equateConf = [
                    "controller" => $controller,
                    "path" => $path,
                    "layout" => "",
                    "package" => "",
                    "base" => $routeConfigPath["base"] .  $controllerName
                ];
                break;
            } elseif ($routeConfigPath["type"] == "regex" && preg_match($routeConfigPath["path"], $path)) {
                $equateConf = $routeConfigPath;
                break;
            } elseif ($routeConfigPath["type"] == "equal" && $routeConfigPath["path"] == $path) {
                $equateConf = $routeConfigPath;
                break;
            }*/
        }

        //print_r($equateConf);
        //exit();

        if ($equateConf !== false) {

            if (is_string($equateConf)) {
                $data["controller"] = $equateConf;

                $file = self::$classPathResolver->findFile($equateConf);

                //problem z symlinkami - zwracają mylącą ścieżkę dla sysstemu
                if (strpos($file, "vendor") !== false) {
                    $tmp = explode("vendor", $file);
                    $file = "vendor" . $tmp[1];
                }

                $xpath = str_replace([ARROW_DOCUMENTS_ROOT, "composer/../", "/"], ["", "", DIRECTORY_SEPARATOR], dirname($file));


                foreach ($packages as $name => $dir) {
                    $dir = str_replace("/", DIRECTORY_SEPARATOR, $dir);

                    $pos = strpos($xpath, $dir);
                    if ($pos === 0 || $pos === 1) {
                        $data["package"] = $name;
                    }
                }
                $data["shortPath"] = trim(str_replace($routeConfigPath, "", $path), "/");
            } else {

                $data["controller"] = $equateConf["__controller"];

                $file = self::$classPathResolver->findFile($data["controller"]);
                if (!$file) {
                    throw new Exception("Cant find route controller `{$data["controller"]}`");
                }


                //@todo zmienić ten sposob na niezalezny od composera
                $file = "/vendor" . explode("vendor", dirname($file))[1];

                $xpath = str_replace(["composer/../", "/"], ["", DIRECTORY_SEPARATOR], dirname($file));

                foreach ($packages as $name => $dir) {
                    $dir = str_replace("/", DIRECTORY_SEPARATOR, $dir);

                    $pos = strpos($xpath, $dir);
                    if ($pos === 0 || $pos === 1) {
                        $data["package"] = $name;
                    }
                }
                $data["shortPath"] = trim(str_replace($routeConfigPath, "", $path), "/");
                if (isset($equateConf["__actionBase"])) {

                    if (substr($data["shortPath"], 0, strlen($equateConf["__actionBase"])) == $equateConf["__actionBase"]) {
                        $data["shortPath"] = substr($data["shortPath"], strlen($equateConf["__actionBase"]));
                    }
                }


                if (isset($equateConf["__actionPrefix"]))
                    $data["shortPath"] = $equateConf["__actionPrefix"] . "/" . $data["shortPath"];
            }
        }

        return $data;
    }

    private static $actions = [];

    public function get($path, $skipRewriteTest = false)
    {

        if (isset(self::$actions[$path])) {
            return self::$actions[$path];
        }

        if (!$skipRewriteTest) {
            $rewriteTest = $this->findByRewrite($path);
            if ($rewriteTest)
                return $rewriteTest;
        }

        $pathInfo = $this->resolvePath($path);
        $action = new Action($pathInfo["path"], $pathInfo["shortPath"], null, $pathInfo["controller"], $pathInfo["package"]);


        self::$actions[$path] = $action;


        return $action;
    }

    public function findByRewrite($path)
    {
        $action = null;


        foreach ($this->configuration["rewrite"] as $_rewrite => $rewrite) {


            if (preg_match_all("/" . $_rewrite . "/", $path, $regs, PREG_SET_ORDER)) {

                $request = RequestContext::getDefault();;
                $c = count($regs[0]);

                if (!is_array($rewrite)) {
                    $action = $this->get($rewrite, true);
                    break;
                }

                for ($i = 1; $i < $c; $i++) {
                    $request->addParameter($rewrite["params"][$i - 1], $regs[0][$i]);
                }
                for ($i = $c - 1; $i < count($rewrite["params"]); $i++) {
                    if (is_array($rewrite["params"][$i])) {
                        $request->addParameter(key($rewrite["params"][$i]), reset($rewrite["params"][$i]));
                    } else
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
            self:: $selfInstance = new Dispatcher();
        }
        return self:: $selfInstance;
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
