<?php
namespace Arrow\Models;
use Arrow\Exception;
use PDOStatement;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 29.10.12
 * Time: 21:08
 * To change this template use File | Settings | File Templates.
 */
class DB extends \PDO
{
    public function query($statement)
    {
        try{

            //\FB::log($statement);
            return parent::query($statement);

        }catch (\Exception $ex){
            /*print "<pre>";
            print_r(array("msg" => $ex->getMessage(), "query"=> $statement));
            exit();*/
            throw new Exception(array("msg" => $ex->getMessage(), "query"=> $statement));
        }

        return null;
    }

    public function execute($statement)
    {
        try{
            //\FB::log($statement);
            return parent::query($statement);

        }catch (\Exception $ex){
            throw new Exception(array("msg" => $ex->getMessage(), "query"=> $statement));
        }

        return null;
    }




}
