<?php

namespace Arrow\Models;

use Arrow\ConfigProvider;
use Arrow\Exception;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\DependencyInjection\Container;

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
    const IAccessHandler = "accessHandler";
    const IExceptionHandler = "exceptionHandler";

    public static $forceDisplayErrors = 1;

    /**
     * Project configuration array
     *
     * @var array
     */
    private $configuration;


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


        /**
     * @var Container
     */
    private $serviceContainer;

    /**
     * @param mixed $serviceContainer
     */
    public function setServiceContainer($serviceContainer): void
    {
        $this->serviceContainer = $serviceContainer;
    }

    /**
     * @return Container
     */
    public function getContainer(){
        return $this->serviceContainer;
    }

    public function __construct($serviceContainer)
    {

        $this->serviceContainer = $serviceContainer;
        self::$instance = $this;

        $this->configuration = ConfigProvider::get();

        if ($this->configuration) {

            $this->id = $this->configuration["name"];

            date_default_timezone_set($this->configuration["timezone"]);

            require_once ARROW_APPLICATION_PATH . "/bootstrap.php";

            $this->getHandler(self::IErrorHandler);
            $this->getHandler(self::IExceptionHandler);
            $this->getHandler(self::ISessionHandler);


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
     * @return Object
     *
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
            print $dbConf['dsn']."<br />";
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