<?php

namespace Arrow\Models;

use Arrow\ViewManager;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 11.09.12
 * Time: 20:16
 * To change this template use File | Settings | File Templates.
 */
abstract class AbstractLayout
{
    abstract public function setTemplate(string $template);

    abstract public function setData(array $data);

    abstract public function generate();


}
