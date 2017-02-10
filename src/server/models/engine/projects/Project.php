<?php

namespace Arrow\Models;

use Arrow\ConfigProvider;
use Arrow\Exception;
use Monolog\Logger;
use Psr\Log\LoggerAwareInterface;

/**
 * Arrow project class
 *
 * @version  1.0
 * @license  GNU GPL
 * @author   Artur Kmera <artur.kmera@arrowplatform.org>
 */
class Project
{

    /**
     * Project conf file name
     */

    const IErrorHandler = "errorHandler";
    const ISessionHandler = "sessionHandler";
    const IAuthHandler = "authHandler";
    const IAccessHandler = "accessHandler";
    const IExceptionHandler = "exceptionHandler";
    const IRemoteResponseHandler = "remoteResponseHandler";


    const CACHE_REFRESH_CONF = 1;
    const CACHE_REFRESH_TEMPLATES = 2;
    const CACHE_REFRESH_STATIC = 4;
    const CACHE_REFRESH_TEMPLATES_FORCE = 10; //8+2 ( normal template refresh )


    public static $cacheFlag = 0;


    public static $forceDisplayErrors = 1;

    /**
     * Project configuration array
     *
     * @var array
     */
    private $configuration;


    /**
     * Project id
     *
     * @var string
     */
    private $id;


    /**
     * Project name
     *
     * @var string
     */
    private $name;


    /**
     * Reference to default project database connection
     *
     * @var \PDO
     */
    private $defaultDbConnection;

    private $accesManager;


    private static $instance;

    /**
     * @return Project|\Arrow\Models\Project
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            throw new \Arrow\Exception("Not implement many projects in one code call");
        }
        return self::$instance;
    }


    public function __construct()
    {
        self::$instance = $this;

        $this->configuration = ConfigProvider::get();

        if ($this->configuration) {
            $this->id = $this->configuration["name"];

            date_default_timezone_set($this->configuration["timezone"]);

            require_once ARROW_APPLICATION_PATH . "/bootstrap.php";

            $this->getHandler(self::IErrorHandler);
            $this->getHandler(self::IExceptionHandler);
            //$this->getHandler(self::ISessionHandler);
            $this->getHandler(self::IAuthHandler);
            $this->accesManager = $this->getHandler(self::IAccessHandler);

        }

    }


    public function getPackages()
    {
        return $this->configuration["packages"];
    }


    /**
     * Returns project name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns project specyfy job handler
     *
     * @return Object
     */
    public function getHandler($handler)
    {
        return call_user_func(array($this->configuration["handlers"][$handler], "getDefault"));
    }


    /**
     * Returns packages conf aray
     *
     * @return array
     */
    public function getPackagesConf()
    {
        return $this->configuration["packages"];
    }

    /**
     * Check is package exists
     *
     * @param string $packageId
     *
     * @return bool
     */
    public function isPackageExist($packageId)
    {
        return isset($this->configuration["packages"][$packageId]);
    }


    /**
     * Returns access manager
     *
     * @return AccessManager
     */
    public function getAccessManager()
    {
        return $this->accesManager;
    }


    /**
     * @return Object
     */
    public function setUpDB($name = false)
    {

        if ($name) {
            throw new \Arrow\Exception(new ExceptionContent("Not implementet [db with name]"));
        }

        $dbConf = $this->configuration["db"];
        try {
            $this->defaultDbConnection = new DB($dbConf['dsn'], $dbConf['user'], $dbConf['password'], [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1]);
        } catch (\Exception $ex) {
            //todo Rozwiązać inaczej :]
            exit("DB connection problem " . $ex->getMessage());
        }
        $this->defaultDbConnection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->defaultDbConnection->exec("SET NAMES utf8");
        $this->defaultDbConnection->exec("SET CHARACTER SET utf8");

    }

    /**
     * @param bool $name
     * @throws \Arrow\Exception
     * @return \PDO
     */
    public function getDB($name = false)
    {
        if (!$name) {
            return $this->defaultDbConnection;
        }

        throw new \Arrow\Exception(new ExceptionContent("Not implementet"));
    }

    public function clearCache()
    {
        $it = new \RecursiveDirectoryIterator(ARROW_CACHE_PATH);
        foreach (new \RecursiveIteratorIterator($it) as $file) {
            if (strpos("" . $file, ".php") || strpos("" . $file, ".js") || strpos("" . $file, ".txt")) {
                unlink($file);
            }
        }
        return 0;

    }


    public function injectLoggers(LoggerAwareInterface $obj)
    {
        $loggers = ConfigProvider::get('loggers');
        $confExist = false;
        if (is_array($loggers)) {
            foreach ($loggers as $class => $data) {
                $objClass = get_class($obj);
                if ($class[0] == "\\") {
                    $class = substr($class, 1);
                }
                if ($objClass == $class) {
                    foreach ($data as $loggerName => $loggerData) {
                        if ($loggerData["active"]) {
                            $logger = new Logger($loggerName);
                            if ($loggerData["handler"] == '\Monolog\Handler\HipChatHandler') {

                                $handler = new \Monolog\Handler\HipChatHandler(
                                    $loggerData["token"], $loggerData["room"], $loggerData["name"], true,
                                    $loggerData["level"], true, true, 'text',
                                    $loggerData["host"],
                                    \Monolog\Handler\HipChatHandler::API_V2
                                );
                            } else {
                                $handler = new $loggerData["handler"]();
                            }
                            $logger->pushHandler($handler);
                            $obj->setLogger($logger);
                            $confExist = true;
                            if (method_exists($obj, "setLogLevel")) {
                                $obj->setLogLevel($loggerData["level"]);
                            }
                        }
                    }
                }
            }
        }

        //iff logger dosnt exists
        if (!$confExist) {
            $logger = new Logger("null");
            $logger->pushHandler(new NullHandler());
            $obj->setLogger($logger);
        }

    }


}

?>