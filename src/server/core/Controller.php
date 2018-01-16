<?php
namespace Arrow;
/**
 * Controller
 *
 *
 * @version 1.0
 * @license  GNU GPL
 * @author Artur Kmera <artur.kmera@arrowplatform.org>
 */
use Arrow\Models\Action;

class Controller
{

    /**
     * Current project
     *
     * @var Project
     */
    public static $project;

    private static $isInCLIMode = null;

    public static function init()
    {
        //Router::setupAction();
        ConfigProvider::init();

        self::$project = new \Arrow\Models\Project(ARROW_APPLICATION_PATH);

    }

    public static function processCall(){

        //paths for server only


        $rq = RequestContext::getDefault();
        Router::getDefault($rq);

        if (Controller::isInCLIMode()) {
            print "Arrowplatform CLI mode. Application '" . ARROW_APPLICATION_PATH . "' " . PHP_EOL;
            \Arrow\Models\ConsoleCommands::process();
        } else {
            \Arrow\Router::getDefault()->process();
            \Arrow\Controller::end();
        }

    }


    /**
     * @return \Arrow\Models\Project
     */
    public static function getProject()
    {
        return self::$project;
    }

    public static function getRunConfiguration(){
        if(self::isInCLIMode())
            return "_console";
        $host = $_SERVER["HTTP_HOST"];
        if(strpos($host,"www.") === 0)
            $host = str_replace( "www.", "", $host );

        return $host;
    }


    public static function isInCLIMode(){
        if(self::$isInCLIMode === null){
            self::$isInCLIMode = (substr(php_sapi_name(), 0, 3) == 'cli');
        }
        return self::$isInCLIMode;
    }

    /**
     * allow all additional modules (logger, db) to close connections and finish execution
     *
     */
    public static function end($response = "")
    {
        \Arrow\ConfigProvider::end();
        @exit($response);
    }
}
