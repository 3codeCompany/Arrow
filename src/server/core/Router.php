<?php

namespace Arrow;

use App\Models\Services;
use Arrow\Models\Action;
use Arrow\Models\AnnotationsDirectoriesLoader;
use Arrow\Models\AnnotationsRouteLoader;

use Arrow\Models\Project;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use const PHP_EOL;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;


/**
 * Router
 *
 * @version  1.0
 * @license  GNU GPL
 * @author   Artur Kmera <artur.kmera@arrowplatform.org>
 * @todo     Rozwinoc o ciastka i pliki, dodac wykrywanie typu wywołania
 */
class Router
{


    /**
     * Template to display
     *
     * @var Action
     */
    private $action;

    /**
     * Router instance
     *
     * @car Router
     */
    private static $oInstance = null;

    private static $basePath = "";


    private $symfonyRouter = null;

    /**
     * @var ContainerBuilder
     */
    private $serviceContainer;

    /**
     * @param mixed $serviceContainer
     */
    public function setServiceContainer($serviceContainer): void
    {
        $this->serviceContainer = $serviceContainer;
    }


    public function getAction()
    {
        return $this->symfonyRouter($this->request->getPathInfo());
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

        $this->request = Request::createFromGlobals();

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
                $defaults["_debug"] = [
                    "file" => str_replace(ARROW_DOCUMENTS_ROOT, "", $tmp->getFileName()),
                    "line" => $tmp->getStartLine(),
                    "template" => Action::generateTemplatePath($defaults)
                ];
                $jsCache[$route->getPath()] = $defaults;
            }

            file_put_contents($file, json_encode($jsCache));
        }
        $this->symfonyRouter = $router;
    }


    public function getBasePath()
    {
        return $this->request->getBasePath();
    }


    public function notFound(Action $action)
    {
        $action->getController()->notFound($action, RequestContext::getDefault());
        exit();
    }

    private function symfonyRouter($path)
    {
//        $this->serviceContainer->get("PhpConsole\Handler")->debug($this->symfonyRouter->getRouteCollection());

        //$this->serviceContainer->get("PhpConsole\Handler")->debug($this->serviceContainer->getAliases());

        try {
            $result = $this->symfonyRouter->match($this->request->getPathInfo()); //'/prefix/cars/index/parametr'

            return new Action(
                $result["_package"],
                $result["_controller"],
                $result["_method"],
                $this->request->getPathInfo(),
                $result
            );

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

    public function process()
    {


        $this->action = $this->symfonyRouter($this->request->getPathInfo());

        $this->action->setServiceContainer($this->serviceContainer);

        if (!$this->action) {
            $this->notFound($this->action);
            return;
        }

        echo $this->action->fetch($this->request);

    }

}
