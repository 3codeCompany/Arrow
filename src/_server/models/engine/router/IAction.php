<?php
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 21/02/13
 * Time: 19:38
 * To change this template use File | Settings | File Templates.
 */

namespace Arrow\Models;


interface IAction {
    public function isAccessible();
    public function exists();
}