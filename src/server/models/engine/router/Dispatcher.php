<?php namespace Arrow\Models;

use Arrow\ConfigProvider;
use Arrow\RequestContext;
use Arrow\Router;
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

    public function get($path, $skipRewriteTest = false)
    {

        if (isset(self::$actions[$path])) {
            return self::$actions[$path];
        }

        if (!$skipRewriteTest) {
            $rewriteTest = $this->findByRewrite($path);
            if ($rewriteTest) {
                return $rewriteTest;
            }
        }

        $pathInfo = $this->resolvePath($path);
        $action = new Action(
            $pathInfo["path"],
            $pathInfo["shortPath"],
            $pathInfo["controller"],
            $pathInfo["package"]
        );


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
                    } else {
                        $request->addParameter($rewrite["params"][$i], null);
                    }
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
