<?php
namespace Arrow\Models;
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 21.12.12
 * Time: 10:03
 * To change this template use File | Settings | File Templates.
 */
//todo uporzadkowac setingsy
class Setting
{
    const SET = "SET";
    const INT = "INT";
    const NUMERIC = "NUMERIC";
    const STRING = "STRING";


    public $name;
    public $title;
    public $type;
    public $values;
    public $description;
}
