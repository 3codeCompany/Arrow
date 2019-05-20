<?php

namespace Arrow;

use Arrow\Models\AbstractLayout;
use Arrow\Models\Action;
use Arrow\Models\AnnotationRouteManager;
use Arrow\Models\AnnotationsDirectoriesLoader;
use Arrow\Models\AnnotationsRouteLoader;
use Arrow\Models\IResponseHandler;
use Arrow\Models\Project;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
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

        $annotatonRouteManager = new AnnotationRouteManager($this->request);
        $this->symfonyRouter = $annotatonRouteManager->getRouter();


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


        try {
            $result = $this->symfonyRouter->match($path);
        } catch (ResourceNotFoundException $ex) {
            exit("Route not found: `" . $path . "`");
        }

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
                    $return->setTemplate(ARROW_PROJECT . $template . ".phtml");
                }


                if (!($return instanceof IResponseHandler)) {
                    $return = new Response(
                        $return->render(),
                        Response::HTTP_OK,
                        array('content-type' => 'text/html')
                    );
                }

            }


            $return->send();


        }


    }

    public function execute($path, Request $request = null, $appendTemplatePath = false)
    {
        $action = $this->symfonyRouter($path);
        $action->setServiceContainer(Project::getInstance()->getContainer());

        $return = $action->fetch($request ?? $this->request, true);

        if ($appendTemplatePath && $return instanceof AbstractLayout) {
            if ($return->getTemplate() == null) {
                $template = Action::generateTemplatePath($action->routeParameters);
                $return->setTemplate(ARROW_PROJECT . $template . ".phtml");
            }
        }

        return $return;
    }

}
