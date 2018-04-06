<?php
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 03.07.13
 * Time: 19:43
 * To change this template use File | Settings | File Templates.
 */

namespace Arrow\Models;


interface IParsersProvider
{

    /**
     * @return ViewParser[]
     */
    public function getParsers();

}