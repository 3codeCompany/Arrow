<?php

namespace Arrow;

use Arrow\Models\AbstractLayout;
use Arrow\Models\Action;
use Arrow\Models\AnnotationsDirectoriesLoader;
use Arrow\Models\AnnotationsRouteLoader;
use Arrow\Models\Project;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function var_dump;


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
            self::$oInstance = new Router();
        }
        return self::$oInstance;
    }

    public function __construct()
    {

        $this->request = Request::createFromGlobals();

        $sourceFolders = [];

        $packages = Project::getInstance()->getPackages();

        $sourceFolders[] = ARROW_APPLICATION_PATH . '/Controllers';
        foreach ($packages as $name => $dir) {
            $sourceFolders[] = ARROW_DOCUMENTS_ROOT . "/" . $dir . "/Controllers";
        }

        AnnotationRegistry::registerLoader('class_exists');

        $routeLoader = new AnnotationsRouteLoader(new AnnotationReader());
        $loader = new AnnotationsDirectoriesLoader(new FileLocator($sourceFolders), $routeLoader);

        $context = new \Symfony\Component\Routing\RequestContext();
        $context->fromRequest($this->request);

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


        $result = $this->symfonyRouter->match($path); //'/prefix/cars/index/parametr'

        return new Action(
            $result["_package"],
            $result["_controller"],
            $result["_method"],
            $path,
            $result
        );


    }

    public function process(Request $request = null)
    {

        if (!$request) {
            $request = $this->request;
        }

        $this->action = $this->symfonyRouter($request->getPathInfo());

        $this->action->setServiceContainer($this->serviceContainer);

        if (!$this->action) {
            $this->notFound($this->action);
            return;
        }

        $return = $this->action->fetch($this->request);

        if ($return !== null) {

            if (is_array($return)) {
                (new JsonResponse($return))->send();

            } elseif ($return instanceof AbstractLayout) {

                if ($return->getTemplate() == null) {
                    $template = Action::generateTemplatePath($this->action->routeParameters);
                    $return->setTemplate(ARROW_DOCUMENTS_ROOT . $template . ".phtml");
                }

                $response = new Response(
                    $return->render(),
                    Response::HTTP_OK,
                    array('content-type' => 'text/html')
                );

                $response->send();
            } else {
                $return->send();
            }

        }

    }

    public function execute($path)
    {
        $action = $this->symfonyRouter($path);
        $action->setServiceContainer($this->serviceContainer);

        return $action->fetch($this->request, true);
    }

}
