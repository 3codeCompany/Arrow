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
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
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

    use ContainerAwareTrait;

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

    private $symfonyRouter = null;

    /**
     * @var Request
     */
    private $request;

    /**
     * Singleton
     *
     * @return Router
     */

    /**
     * @var bool
     */
    private $inDevMode = false;

    public static function getDefault($serviceContainer = null)
    {
        if (self::$oInstance == null) {
            self::$oInstance = new Router($serviceContainer);

        }
        return self::$oInstance;
    }

    public function __construct($container)
    {
        $this->container = $container;

        $this->request = $this->container->get(Request::class);

        /** @var StateProvider $regenerate */
        $this->inDevMode = (bool)\getenv("APP_DEBUG_LIVE_ROUTING_SCAN") || $this->request->cookies->get("ARROW_DEBUG_WEBPACK_DEV_SERVER");


        $sourceFolders = [];

        $packages = Project::getInstance()->getPackages();

        $sourceFolders[] = ARROW_APPLICATION_PATH . '/Controllers';
        foreach ($packages as $name => $dir) {
            $sourceFolders[] = ARROW_PROJECT . "/" . $dir . "/Controllers";
        }

        AnnotationRegistry::registerLoader('class_exists');

        $routeLoader = new AnnotationsRouteLoader(new AnnotationReader());
        $loader = new AnnotationsDirectoriesLoader(new FileLocator($sourceFolders), $routeLoader);

        $context = new \Symfony\Component\Routing\RequestContext();
        $context->fromRequest($this->request);

        $router = new \Symfony\Component\Routing\Router(
            $loader,
            $sourceFolders,
            ($this->inDevMode ? [] : ['cache_dir' => ARROW_CACHE_PATH . "/symfony"]),
            $context
        );


        $file = ARROW_CACHE_PATH . "/symfony/route.json";
        if ($this->inDevMode || !file_exists($file)) {
            $col = $router->getRouteCollection();
            $jsCache = [];

            foreach ($col as $route) {
                $defaults = $route->getDefaults();

                $tmp = new \ReflectionMethod ($defaults["_controller"], $defaults["_method"]);
                $templatePath = Action::generateTemplatePath($defaults) . ".component.tsx";
                $defaults["_debug"] = [
                    "file" => str_replace(ARROW_PROJECT, "", $tmp->getFileName()),
                    "line" => $tmp->getStartLine(),
                    "template" => Action::generateTemplatePath($defaults),
                    "templateExists" => file_exists(ARROW_PROJECT . $templatePath)
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

    /**
     * @return null|\Symfony\Component\Routing\Router
     */
    public function getSymfonyRouter(): ?\Symfony\Component\Routing\Router
    {
        return $this->symfonyRouter;
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

        $this->action->setServiceContainer($this->container);

        if (!$this->action) {
            $this->notFound($this->action);
            return;
        }


        $return = $this->action->fetch($this->request);

        if ($return !== null) {

            if (is_array($return)) {
                $return = (new JsonResponse($return));

            } elseif ($return instanceof AbstractLayout) {

                if ($return->getTemplate() == null) {
                    $template = Action::generateTemplatePath($this->action->routeParameters);
                    $return->setTemplate(ARROW_DOCUMENTS_ROOT . $template . ".phtml");
                }
                $return = new Response(
                    $return->render(),
                    Response::HTTP_OK,
                    array('content-type' => 'text/html')
                );

            }
            if ($this->inDevMode) {
                $return->headers->set("ARROW_DEBUG_ROUTE_HASH", hash_file('md5', ARROW_CACHE_PATH . "/symfony/route.json"));
            }
            $return->send();


        }


    }

    public function execute($path, Request $request = null)
    {
        $action = $this->symfonyRouter($path);
        $action->setServiceContainer(Project::getInstance()->getContainer());

        return $action->fetch($request ?? $this->request, true);
    }

}
