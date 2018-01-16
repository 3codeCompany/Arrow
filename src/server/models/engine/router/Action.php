<?php

namespace Arrow\Models;

/**
 * Created by JetBrains PhpStorm.
 * User: Artur
 * Date: 22.09.12
 * Time: 12:26
 * To change this template use File | Settings | File Templates.
 */
use Arrow\Exception;
use Arrow\Access\Models\AccessAPI;
use Arrow\RequestContext;
use function htmlentities;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use function var_dump;

class Action
{


    private $path;
    private $method;
    private $controller;
    /**
     * @var AbstractLayout
     */
    private $layout;

    private $package;
    private $routeParameters;

    private $request;


    /**
     * //todo make private
     * @var array
     */
    public $vars = array();


    public function __construct($package, $controller, $method, $path, $routeParameters)
    {
        $this->path = $path;
        $this->method = $method;
        $this->controller = $controller;
        $this->package = $package;
        $this->routeParameters = $routeParameters;

    }

    public function fetch(Request $request)
    {

        /**
         * Access check
         */
        $method = $this->method;
        $instance = new $this->controller($request, $this);

        if (!$this->isAccessible()) {
            AccessAPI::accessDenyProcedure($this->path . " " . $this->package);
        }

        //$instance->view = $view;
        $instance->eventRunBeforeAction($this, $request);
        $return = $instance->$method($request);

        if ($return) {
            $return->send();
        }
    }

    public function getTemplatePath()
    {
        return self::generateTemplatePath($this->routeParameters);
    }

    public static function generateTemplatePath($defaults)
    {
        $packages = Project::getInstance()->getPackages();
        $controllerExploded = explode("\\Controllers\\", $defaults["_controller"]);
        return ($defaults["_package"] == "app" ? "/app" : "/" . $packages[$defaults["_package"]]) .
            "/views/" . str_replace("\\", "/", strtolower($controllerExploded[1])) .
            "/" . $defaults["_method"];
    }

    public function getRequest()
    {
        return RequestContext::getDefault();
    }


    public function getPackage()
    {
        return $this->package;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getRoute()
    {
        return $this->package . $this->path;
    }


    function __toString()
    {
        return $this->path;
    }

    public function isAccessible()
    {
        return AccessAPI::checkAccess("view", "show", $this->getPath(), "");
    }

    public function assign($var, $value)
    {
        $this->vars[$var] = $value;
    }


}
