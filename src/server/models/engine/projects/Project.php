<?php
namespace Arrow\Models;

use Arrow\Exception;

/**
 * Arrow project class
 *
 * @version  1.0
 * @license  GNU GPL
 * @author   Artur Kmera <artur.kmera@arrowplatform.org>
 */
class Project implements \Arrow\ICacheable
{

    /**
     * Project conf file name
     */

    const PROJECT_CONF_FILE = "/conf/project-conf.xml";

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


    private $projectToServerRelative;

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
     * Project path
     *
     * @var string
     */
    private $path;

    /**
     * Reaction on error ( display | rediect )
     *
     * @var integer
     */
    private $errorOnError = "display";

    /**
     * Error display level (none|low|medium|high|strict)
     *
     * @var integer
     */
    private $errorDisplayLevel = "all";

    /**
     * Error log level (none|low|medium|high|strict)
     *
     * @var integer
     */
    private $errorLogLevel = "high";

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

    /**
     * ActionDescriptor constructor
     *
     * @param integer $id
     * @param string  $name
     * @param string  $path
     * @param boolean $isDefault
     */
    public function __construct($path = false)
    {

        self::$instance = $this;
        $this->path = $path;

        $confFile = $this->getPath() . "/" . self :: PROJECT_CONF_FILE;


        $this->configuration = \Arrow\CacheProvider::getFileCache($this, $confFile, array("file_prefix" => "project_config"));
        if($this->configuration) {
            $this->id = $this->configuration["id"];

            date_default_timezone_set($this->configuration["timezone"]);


            $this->configuration["settings"] = Settings::init($this->configuration["settings"]);


            require_once ARROW_APPLICATION_PATH . "/bootstrap.php";

            $this->getHandler(self::IErrorHandler);
            $this->getHandler(self::IExceptionHandler);
            //$this->getHandler(self::ISessionHandler);
            $this->getHandler(self::IAuthHandler);
            $this->accesManager = $this->getHandler(self::IAccessHandler);

        }

    }

    /**
     * (non-PHPdoc)
     *
     * @see core/\Arrow\ICacheable#generateCache()
     */
    public function generateCache($params)
    {
        $tmp = array();
        $projectConf = $this->getXmlConfDocument();


        $tmp["id"] = "" . $projectConf->id;
        $tmp["timezone"] = "" . $projectConf->timezone;

        $tmp["settings"] = \Arrow\Models\Settings::getDefault()->parseSetingsXML($projectConf->settings->section, "application");

        $tmp["handlers"] = array();
        foreach ($projectConf->handlers->handler as $handler) {
            $tmp["handlers"][(string)$handler["name"]] = (string)$handler["model"];
        }


        $tmp["packages"] = array();

        $tmp["packages"]["application"] = array("namespace" => "application", "name" => "application", "dir" => ARROW_APPLICATION_PATH );

        foreach ($projectConf->packages->package as $package) {
            $dir = (string)isset($package["dir"]) ? "" . $package["dir"] : ARROW_PACKAGES_PATH . DIRECTORY_SEPARATOR . $package["name"];
            $tmp["packages"][(string)$package["namespace"]] = array(
                "namespace" => (string)$package["namespace"],
                "name" => (string)$package["name"],
                "dir" => $dir
            );
            $settingsFile = $dir . "/conf/settings.xml";
            if (file_exists($settingsFile)) {
                $xml = simplexml_load_file($settingsFile);
                $tmpSettings = \Arrow\Models\Settings::getDefault()->parseSetingsXML($xml->section, $package["namespace"] . "");

                $tmp["settings"] = array_merge($tmp["settings"], $tmpSettings);
            }
        }



        return $tmp;
    }



    /**
     * Get xml-conf document - read conf document from project conf file
     *
     * @return simpleXMLElement
     */
    private function getXmlConfDocument()
    {
        return simplexml_load_file(ARROW_APPLICATION_PATH. self :: PROJECT_CONF_FILE);
    }


    public function getPackages()
    {
        return $this->configuration["packages"];
    }

    public function packageExists($packageNamespace)
    {
        foreach ($this->getPackages() as $package) {
            if ($package["namespace"] == $packageNamespace) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns project Id
     *
     * @return integer
     */
    /*	public function getId() {
            return $this->id;
        }*/

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
     * Returns project path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }





    /**
     * Returns relative path eg ./projects/cms
     *
     * @return string
     */
    public function getRelativePath()
    {
        $path = $this->getPath();

        $path = "." . str_replace(ARROW_ROOT_PATH, "", $path);

        return $path;
    }

    /**
     * Returns proect default flag
     *
     * @return string
     */
    public function isDefault()
    {
        return $this->isDefault;
    }

    /**
     * Returns on error reaction
     *
     * @return string
     */
    public function getErrorOnError()
    {
        return $this->errorOnError;
    }

    /**
     * Returns Error display level
     *
     * @return string
     */
    public function getErrorDisplayLevel()
    {
        return $this->errorDisplayLevel;
    }

    /**
     * Returns Error log level
     *
     * @return string
     */
    public function getErrorLogLevel()
    {
        return $this->errorLogLevel;
    }


    /**
     * Return all configuration variables
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->configuration["settings"];
    }

    public function getSetting($setting_name)
    {
        return $this->configuration["settings"]->getSetting($setting_name);
    }

    /**
     * Returns xml config
     *
     * @param String $configType Type like Project::BEANS_CONF
     *
     * @return SimpleXMLElement
     */
    public function getXMLConfig($configType)
    {
        return simplexml_load_file($this->getPath() . $configType);
    }

    public function saveXMLConfig($configType, $xmlDoc)
    {
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xmlDoc->asXml());
        file_put_contents($this->getPath() . $configType, $dom->saveXML());
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
     * Sets on error reaction ( display|redirect )
     *
     * @param String $reaction
     *
     * @return void
     */
    public function setOnError($reaction)
    {
        $this->errorOnError = $reaction;
    }

    /**
     * Sets error display level
     *
     * @param String $level
     *
     * @return void
     */
    public function setErrorDisplayLevel($level)
    {
        $this->errorDisplayLevel = $level;
    }

    /**
     * Sets error log level
     *
     * @param String $level
     *
     * @return void
     */
    public function setErrorLogLevel($level)
    {
        $this->errorLogLevel = $level;
    }


    /**
     * IObjectSerialize implementaction
     *
     * @return array Object in array
     */
    public function serialize()
    {
        $val = array();
        $val["id"] = $this->getId();
        $val["name"] = $this->getName();
        $val["isDefault"] = $this->isDefault();
        $val["errorOnError"] = $this->getOnError();
        $val["errorDisplayLevel"] = $this->getErrorDisplayLevel();
        $val["errorLogLevel"] = $this->getErrorLogLevel();
        return $val;
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
     * Returns handle for resources
     *
     * @return Resources
     */
    public function getResources()
    {
        return Resources::getDefault($this);
    }

    /**
     * @return Object
     */
    public function setUpDB($name = false)
    {

        if($name){
            throw new \Arrow\Exception(new ExceptionContent("Not implementet [db with name]"));
        }

        $dbConf = Settings::getDefault()->getSetting("application.db");
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

    public function addOperation($path, $controller = false, $package = false)
    {


    }

    public function addView($layout, $path, $controller = false, $package = false)
    {
        $packages = $this->getPackages();
        $package = $packages[$package ? $package : 'application'];
        $dir = $package["dir"];
        $conf = $dir . "/conf/route.xml";
        $xml = simplexml_load_file($conf);
        $section = $xml->xpath("/route/views/section[@layout='{$layout}']");
        print $section;


        exit("");
    }


}

?>