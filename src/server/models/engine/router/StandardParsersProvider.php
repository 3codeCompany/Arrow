<?php
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 03.07.13
 * Time: 19:39
 * To change this template use File | Settings | File Templates.
 */

namespace Arrow\Models;


use Arrow\Router;

class StandardParsersProvider implements IParsersProvider
{
    /**
     * @return ViewParser[]
     */
    public function getParsers()
    {
        return [
            (new ViewParser('/(import|t|o|r|v):(\.|)\/([\w\/.-]+)/', function ($matches) {
                if ($matches[2] == ".")
                    $matches[3] = $matches[2] . "/" . $matches[3];
                if ($matches[1] == "r")
                    return \Arrow\Controller::$project->getResources()->getResource('/' . $matches[3])->getRelativePath();
                if ($matches[1] == "o" || $matches[1] == "v")
                    return Router::link($matches[3]);
                if ($matches[1] == "import")
                    return Dispatcher::getDefault()->get($matches[3])->generate();
            })),
            (new ViewParser('/r:(\w*?)::([\w\/.-]+)/', function ($matches) {
                $package = $matches[1] ? $matches[1] : 'application';
                return \Arrow\Controller::$project->getResources()->getResource($matches[2], $package)->getRelativePath();
            }))

        ];
    }
}