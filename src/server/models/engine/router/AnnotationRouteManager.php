<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 19.04.2018
 * Time: 23:48
 */

namespace Arrow\Models;


use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;

class AnnotationRouteManager
{


    /**
     * @var Router
     */
    private $router;

    /**
     * AnnotationRouteManager constructor.
     */
    public function __construct(Request $request)
    {
        $sourceFolders = [];

        $packages = Project::getInstance()->getPackages();

        $sourceFolders[] = ARROW_APPLICATION_PATH . '/Controllers';
        foreach ($packages as $name => $dir) {
            $sourceFolders[] = ARROW_PROJECT . "/" . $dir . "/Controllers";
        }

        AnnotationRegistry::registerLoader('class_exists');

        $routeLoader = new AnnotationsRouteLoader(new AnnotationReader());
        $loader = new AnnotationsDirectoriesLoader(new FileLocator($sourceFolders), $routeLoader);

        $context = new RequestContext();
        $context->fromRequest($request);

        $this->router = new Router(
            $loader,
            $sourceFolders,
            (ARROW_IN_DEV_STATE ? [] : ['cache_dir' => ARROW_CACHE_PATH . "/symfony"]),
            $context
        );
    }

    /**
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }


    public function exposeRouting()
    {

        //$file = ARROW_CACHE_PATH . "/symfony/route.json";
        //if ($this->inDevMode || !file_exists($file)) {
        $col = $this->router->getRouteCollection();
        $jsCache = [];

        foreach ($col as $route) {
            $defaults = $route->getDefaults();

            $tmp = new \ReflectionMethod ($defaults["_controller"], $defaults["_method"]);

            $templatePath = Action::generateTemplatePath($defaults);
            $defaults["_debug"] = [
                "file" => str_replace(ARROW_PROJECT, "", $tmp->getFileName()),
                "line" => $tmp->getStartLine(),
                "template" => Action::generateTemplatePath($defaults),
                "componentExists" => file_exists(ARROW_PROJECT ."/". $templatePath . ".component.tsx"),
                "templateExists" => file_exists(ARROW_PROJECT . $templatePath . ".phtml"),
            ];
            $jsCache[$route->getPath()] = $defaults;
        }

        return $jsCache;

    }

}