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
    abstract public function createLayout(ViewManager $manager);

    abstract public function getLayoutFile();

    public function getFileName($path)
    {
        return $path . ".phtml";
    }

    public function getFirstTemplateContent(Action $action)
    {
        return false;
    }

}
