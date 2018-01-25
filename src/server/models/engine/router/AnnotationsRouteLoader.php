<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 10.01.2018
 * Time: 09:47
 */

namespace Arrow\Models;


use Symfony\Component\Routing\Loader\AnnotationClassLoader;
use Symfony\Component\Routing\Route;
use function strtolower;

class AnnotationsRouteLoader extends AnnotationClassLoader
{
    protected function configureRoute(
        Route $route,
        \ReflectionClass $class,
        \ReflectionMethod $method,
        $annot
    ) {

        //print "cos<br />";

        $className = $class->getName();

        $exploded = explode("\\", $className);
        $package = "app";
        if ($exploded[0] != "App") {
            $package = strtolower($exploded[1]);

            $route->setPath($package . $route->getPath());
        }


        $route->setDefaults([
            "_controller" => $className,
            "_method" => $method->getName(),
            "_package" => $package,
            "_routePath" => $route->getPath()
        ]);


    }

}
