<?php

namespace Arrow\Models;

/**
 * Created by JetBrains PhpStorm.
 * User: Artur
 * Date: 22.09.12
 * Time: 12:26
 * To change this template use File | Settings | File Templates.
 */
use Arrow\Access\Models\AccessAPI;
use Arrow\RequestContext;
use Arrow\Router;
use Exception;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\AutowiringFailedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use const ARROW_DOCUMENTS_ROOT;
use function is_array;
use function var_dump;

class Action
{


    private $path;
    private $method;
    private $controller;

    private $package;
    public $routeParameters;

    private $request;

    /**
     * @var ContainerBuilder
     */
    private $serviceContainer;


    public function __construct($package, $controller, $method, $path, $routeParameters)
    {
        $this->path = $path;
        $this->method = $method;
        $this->controller = $controller;
        $this->package = $package;
        $this->routeParameters = $routeParameters;

    }

    /**
     * @return mixed
     */
    public function getServiceContainer()
    {
        return $this->services;
    }

    /**
     * @param mixed $services
     */
    public function setServiceContainer($serviceContainer): void
    {
        $this->serviceContainer = $serviceContainer;
    }

    private function resolveClassDependancy(ReflectionClass $dependencyClass)
    {

        $dependencyClassName = $dependencyClass->getName();

        // see service container for exact implementation
        if ($this->serviceContainer->has($dependencyClassName)) {
            return $this->serviceContainer->get($dependencyClassName);
        }

        // try to match by interfaces
        $interfaces = $dependencyClass->getInterfaces();
        foreach ($interfaces as $interface) {
            $resolvedService = $this->resolveClassDependancy($interface);
            if (null !== $resolvedService) {
                return $resolvedService;
            }
        }
        // fallback to parent class
        if ($parentClass = $dependencyClass->getParentClass()) {
            return $this->resolveClassDependancy($parentClass);
        }
    }

    public function fetch(Request $request)
    {

        /**
         * Access check
         */
        if (!$this->isAccessible()) {
            AccessAPI::accessDenyProcedure($this->getPath());
        }

        //dependency injection
        $reflector = new ReflectionClass($this->controller);
        $methodArguments = $reflector->getMethod($this->method)->getParameters();

        $constructor = $reflector->getConstructor();
        $constructorArguments = [];
        if ($constructor) {
            $constructorArguments = $reflector->getConstructor()->getParameters();
        }

        $toResolve = ["constructor" => $constructorArguments, "method" => $methodArguments];
        $preparedArgs = [];

        foreach ($toResolve as $key => $arguments) {
            $argumentClassHint = [];
            foreach ($arguments as $argumentIndex => $argument) {
                $classHint = $argument->getClass();
                if ($classHint) {
                    $argumentClassHint[$argumentIndex] = $classHint;
                } else {
                    $argumentClassHint[$argumentIndex] = false;
                }
            }

            $preparedArgs[$key] = [];
            foreach ($argumentClassHint as $index => $hint) {
                if ($hint) {
                    if($hint->getName() == Request::class){
                        $injection = $request;
                    }else {
                        $injection = $this->resolveClassDependancy($hint);
                    }
                    if ($injection === null) {
                        throw new AutowiringFailedException($hint->getName(), "Service '{$hint->getName()}' not found [ Controller: {$this->controller}::{$this->method}] ");
                    } else {
                        $preparedArgs[$key][$index] = $injection;
                    }


                } else {
                    if ($key != "constructor") {
                        $name = $methodArguments[$index]->getName();

                        if (isset($this->routeParameters[$name])) {
                            $preparedArgs[$key][$index] = $this->routeParameters[$name];
                        } else {
                            $preparedArgs[$key][$index] = $methodArguments[$index]->getDefaultValue();
                        }
                    }
                }
            }
        }


        $instance = new $this->controller(...$preparedArgs["constructor"]);

        if ($instance instanceof Controller) {
            $instance->eventRunBeforeAction($this, $request);
        }



        return $instance->{$this->method}(...$preparedArgs["method"]);

    }

    public function getTemplatePath()
    {
        return self::generateTemplatePath($this->routeParameters);
    }

    public static function generateTemplatePath($defaults)
    {
        $packages = Project::getInstance()->getPackages();
        $controllerExploded = explode("\\Controllers\\", $defaults["_controller"]);
        return ($defaults["_package"] == "app" ? "/app" :  $packages[$defaults["_package"]]) .
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
        return AccessAPI::checkAccess("view", "show", $this->routeParameters["_routePath"], "");
    }


}
