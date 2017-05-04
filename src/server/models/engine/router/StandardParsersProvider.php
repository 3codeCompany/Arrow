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
            (new ViewParser('/(import):(\.|)\/([\w\/.-]+)/', function ($matches) {
                return Dispatcher::getDefault()->get($matches[3])->generate();
            })),
        ];
    }
}