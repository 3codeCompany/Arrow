<?php

namespace Arrow;

use Symfony\Component\Dotenv\Dotenv;

/**
 * Controller
 *
 *
 * @version 1.0
 * @license  GNU GPL
 * @author Artur Kmera <artur.kmera@arrowplatform.org>
 */
use App\Models\Services;
use Arrow\Models\Action;
use Arrow\Models\Project;

class Kernel
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

        if (file_exists(ARROW_PROJECT . '/.env')) {
            (new Dotenv())->load(ARROW_PROJECT . '/.env');
        }
        //Router::setupAction();
        ConfigProvider::init();

        self::$project = new \Arrow\Models\Project((new Services())->buildContainer());
        //self::$project->setServiceContainer();

    }


    public static function processCall()
    {
        //paths for server only
        $router = Router::getDefault(self::$project->getContainer());
        $router->process();
        \Arrow\Kernel::end();

    }


    /**
     * @return \Arrow\Models\Project
     */
    public static function getProject()
    {
        return self::$project;
    }

    public static function getRunConfiguration()
    {
        if (self::isInCLIMode()) {
            return "_console";
        }
        $host = $_SERVER["HTTP_HOST"];
        if (strpos($host, "www.") === 0) {
            $host = str_replace("www.", "", $host);
        }

        return $host;
    }


    public static function isInCLIMode()
    {
        if (self::$isInCLIMode === null) {
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
