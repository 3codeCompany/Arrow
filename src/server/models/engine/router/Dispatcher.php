<?php namespace Arrow\Models;

use Arrow\ConfigProvider;
use Arrow\Router;
use const ARROW_CACHE_PATH;
use const ARROW_DEV_MODE;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use function file_exists;
use function file_put_contents;
use function json_encode;
use const PHP_EOL;
use function str_replace;
use function strtolower;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use const ARROW_APPLICATION_PATH;
use const ARROW_DOCUMENTS_ROOT;
use function array_slice;
use function ucfirst;

/**
 * Arrow template structure
 *
 * @version  1.0
 * @license  GNU GPL
 * @author   Artur Kmera <artur.kmera@arrowplatform.org>
 */
class Dispatcher
{

    public function resolvePath($path)
    {

        if ($path[0] == ".") {
            $tmp = explode("/", Router::getActionParameter());
            $tmp[count($tmp) - 1] = substr($path, 2);
            $path = implode("/", $tmp);

        }
        $path = $path[0] == "/" ? $path : "/" . $path;
        $packages = Project::getInstance()->getPackages();


        $tmp = explode("/", trim($path, "/"));

        $c = count($tmp);
        if ($c >= 2) {
            if ($c == 2) {
                array_unshift($tmp, 'app');
            }


            if (isset($packages[$tmp[0]]) || $tmp[0] == "app") {
                $package = $tmp[0];
                $controller = array_slice($tmp, 1, count($tmp) - 2);
            } else {
                $package = 'app';
                $controller = array_slice($tmp, 0, count($tmp) - 1);
            }
            $action = end($tmp);
            foreach ($controller as &$el) {
                $el = ucfirst($el);
            }
            $controller = implode("\\", $controller);

            $_tmpData = [
                "package" => $package,
                "controller" => ($package == "app" ? 'App\\' : "Arrow\\" . ucfirst($package) . "\\") . "Controllers\\" . $controller,
                "action" => $action,
                "packagePath" => $package == "app" ? "./app/" : $packages[$package],
                "path" => str_replace("/" . trim($package, "/"), "", $path)
            ];


            return [
                "path" => $_tmpData["path"],
                "shortPath" => $action,
                "controller" => $_tmpData["controller"],
                "package" => $_tmpData["package"],
            ];

        }


        throw new \Exception("Not mapped path: `{$path}` ");

    }

    private static $actions = [];


    private function symfonyRouter($path)
    {
        $sourceFolders = [];
        $autoload = require ARROW_DOCUMENTS_ROOT . "/vendor/autoload.php";


        $packages = Project::getInstance()->getPackages();

        $sourceFolders[] = ARROW_APPLICATION_PATH . '/Controllers';
        foreach ($packages as $name => $dir) {
            $sourceFolders[] = ARROW_DOCUMENTS_ROOT . "/" . $dir . "/Controllers";
        }


        AnnotationRegistry::registerLoader(array($autoload, 'loadClass'));

        $routeLoader = new AnnotationsRouteLoader(new AnnotationReader());
        $loader = new AnnotationsDirectoriesLoader(new FileLocator($sourceFolders), $routeLoader);
        $request = Request::createFromGlobals();
        $context = new \Symfony\Component\Routing\RequestContext();
        $context->fromRequest($request);

        $router = new \Symfony\Component\Routing\Router(
            $loader,
            $sourceFolders,
            (ARROW_DEV_MODE ? [] : ['cache_dir' => ARROW_CACHE_PATH . "/symfony"]),
            $context
        );

        $file = ARROW_CACHE_PATH . "/symfony/route.json";
        if (ARROW_DEV_MODE || !file_exists($file)) {
            $col = $router->getRouteCollection();
            $jsCache = [];

            foreach ($col as $route) {
                $defaults = $route->getDefaults();
                $tmp = new \ReflectionMethod ($defaults["_controller"], $defaults["_method"]);
                $controllerExploded = explode("\\Controllers\\", $defaults["_controller"]);


                $defaults["_debug"] = [
                    "file" => str_replace(ARROW_DOCUMENTS_ROOT, "", $tmp->getFileName()),
                    "line" => $tmp->getStartLine(),
                    "template" =>
                        ($defaults["_package"] == "app" ? "/app" : "/" . $packages[$defaults["_package"]]) .
                        "/views/" . str_replace("\\", "/", strtolower($controllerExploded[1])) .
                        "/" . $defaults["_method"]

                ];
                $jsCache[$route->getPath()] = $defaults;
            }

            file_put_contents($file, json_encode($jsCache));
        }

        try {
            $result = $router->match($request->getPathInfo()); //'/prefix/cars/index/parametr'


            return [
                "path" => $request->getPathInfo(),
                "shortPath" => $result["_method"],
                "controller" => $result["_controller"],
                "package" => $result["_package"],
            ];

        } catch (ResourceNotFoundException $ex) {


            /*print "<pre>";
            print $request->getPathInfo() . PHP_EOL;
            print_r($router->getRouteCollection());
            exit()*/;
            return false;
            //print_r($ex);
            //print "<h1>{$ex->getMessage()}</h1>";
        }
    }

    public function get($path)
    {

        $pathInfo = $this->symfonyRouter($path);

        if ($pathInfo == false) {
            if (isset(self::$actions[$path])) {
                return self::$actions[$path];
            }
            $pathInfo = $this->resolvePath($path);
        }

        $action = new Action(
            $pathInfo["path"],
            $pathInfo["shortPath"],
            $pathInfo["controller"],
            $pathInfo["package"]
        );


        self::$actions[$path] = $action;


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
