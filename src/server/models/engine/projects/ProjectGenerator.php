<?php
namespace Arrow\Models;
use Arrow\Controller;
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 19.12.12
 * Time: 16:02
 * To change this template use File | Settings | File Templates.
 */
class ProjectGenerator
{
    /**
     * Directory with new project templates
     *
     * @var string
     */
    private $templtesDir = "";

    /**
     * @var string Project id
     */
    private $projectId;

    /**
     * @var string Project name
     */
    private $projectName;

    /**
     * @var directory with server documents root
     */
    private $httpDocumentsRoot;

    /**
     * @var string Project directory
     */
    private $projectDirectory;

    /**
     * Project settings
     *
     * Arguments order: name, value, [title], [type], [possible values], [configuration], [description]
     *
     * @var array
     */
    private $projectSettings
        = array(
            "DB access"       => array(
                array("db.dsn", "mysql:dbname=test;host=127.0.0.1", "Data source name"),
                array("db.user", "db_user", "Database user"),
                array("db.password", "db_password", "Database password"),
                array("db.dsn", "mysql:dbname=test;host=127.0.0.1", "Data source name", false, false, "deploy-server.com"),
                array("db.user", "db_user", "Database user", false, false, "deploy-server.com"),
                array("db.password", "db_password", "Database password", false, false, "deploy-server.com")

            ),
            "System settings" => array(
                array("panel.title", "Arrowplatform Panel", "Title for admininstration panel"),
                array("view.default.layout", "Arrow\\Package\\Application\\PresentationLayout", "Default view", "Default application view"),
                array("view.default.view", "index", "Default view", false, false, false, "Default application view"),
                array("view.login", "auth/login", "Login view", false, false, false, "View used to login into application"),
                array("error.view", "error", "Error view", false, false, false, "View presented on error"),
                array("error.mail", "error", "Error mail", false, false, false, "Email used to send information abaut errors"),
            ),
            "Other"           => array()

        );

    /**
     * Project default packages
     *
     * @var array
     */
    private $packages
        = array(
            "access"        => "org.arrowplatform.access",
            "common"        => "org.arrowplatform.common",
            "media"         => "org.arrowplatform.media",
            "utils"         => "org.arrowplatform.utils",
            "communication" => "org.arrowplatform.communication",
        );

    /**
     * Project timezone
     *
     * @var string
     */
    private $timezone = "Europe/Warsaw";

    /**
     * Project default loaders
     *
     * @var array
     */
    private $loaders
        = array(
            "/libraries/controls/src/Loader.php" => '\Arrow\Controls\Loader::registerAutoload',
            "/libraries/ORM/Loader.php"      => '\Arrow\ORM\Loader::registerAutoload'
        );


    private $handlers
        = array(
            "authHandler"           => "\\Arrow\\Package\\Access\\Auth",
            "accessHandler"         => "Arrow\\Package\\Access\\AccessManager",
            "errorHandler"          => "Arrow\\Models\\ErrorHandler",
            "exceptionHandler"      => "Arrow\\Models\\ExceptionHandler",
            "remoteResponseHandler" => "Arrow\\Models\\JSONRemoteResponseHandler",
            "sessionHandler"        => "Arrow\\Models\\SessionHandler",
        );

    /**
     * @param string $name
     * @param string $dir
     * @param string $httpDocumentsRoot
     */
    public function __construct($id, $name, $dir, $httpDocumentsRoot)
    {
        $this->projectId = $id;
        $this->projectName = $name;
        $this->projectDirectory = $dir;
        $this->httpDocumentsRoot = $httpDocumentsRoot;

        $this->templtesDir = ARROW_SERVER_PATH . "/resources/generatorTemplates/";
    }

    /**
     * Changes or sets setting in project
     *
     * @param string $name
     * @param string $value
     * @param        string bool $title
     * @param        string bool $type
     * @param        string bool $possibleValues
     * @param        string bool $configuration
     * @param        string bool $description
     */
    public function setSetting($name, $value, $title = false, $type = false, $possibleValues = false, $configuration = false, $description = false)
    {
        $set = array($name, $value, $title, $type, $possibleValues, $configuration, $description);

        foreach ($this->projectSettings as &$section) {
            foreach ($section as &$setting) {
                if ($setting[0] == $name) {
                    $setting[1] = $value;
                    return;
                }
            }
        }
        $this->projectSettings["Other"][] = $set;

    }

    /**
     * Adding package to project
     *
     * @param $namespace
     * @param $fullName
     */
    public function addPackage($namespace, $fullName)
    {
        if (file_exists(ARROW_PACKAGES_PATH . "/" . $fullName)) {
            $this->packages[$namespace] = $fullName;
        } else {
            throw new \Arrow\Exception("Package `$fullName` not exists in packages directory");
        }
    }

    /**
     * Generates directory structure in project dir
     *
     * @throws \Arrow\Exception
     */
    private function generateStructure()
    {
        if (file_exists($this->projectDirectory)) {
            throw new \Arrow\Exception("Project directory already exists");
        }

        mkdir($this->projectDirectory);

        $applicationDirectories = array(
            "data", "data/deploy", "data/logs", "data/sessions",
            "data/cache", "data/cache/db/", "data/cache/forms/", "data/cache/img/", "data/cache/static/", "data/cache/templates/", "data/cache/models_cache/", "data/uploads",
            "resources", "resources/img",
            "libraries",
            "scripts",
            "tests",
            "application", "application/conf", "application/controllers", "application/layouts", "application/models", "application/views",
        );

        foreach ($applicationDirectories as $dir) {
            mkdir($this->projectDirectory . "/" . $dir);
        }
    }

    /**
     * Generates 'index.php' file and setting application paths
     */
    private function generateIndex()
    {
        $source = file_get_contents($this->templtesDir . "index.txt");

        $replace = array(
            "{documentsRoot}"   => str_replace("\\", "/", $this->httpDocumentsRoot),
            "{arrowRootPath}"   => str_replace("\\", "/", realpath(__DIR__ . "/../../../..//../")),
            "{applicationPath}" => str_replace("\\", "/", $this->projectDirectory)
        );

        $source = strtr($source, $replace);

        file_put_contents($this->projectDirectory . "/index.php", $source);

    }

    /**
     * Generates Loader class
     */
    private function generateBootstrap()
    {
        $source = file_get_contents($this->templtesDir . "bootstrap.txt");
        file_put_contents($this->projectDirectory . "/application/bootstrap.php", $source);

    }


    /**
     * Generates 'bootstrap.php' file
     */
    private function generateLoader()
    {
        $source = file_get_contents($this->templtesDir . "Loader.txt");
        file_put_contents($this->projectDirectory . "/application/Loader.php", $source);

    }

    /**
     * Generates project configuration file
     */
    private function generateConfProject()
    {
        $itemsXml = new \SimpleXMLElement("<project></project>");
        $itemsXml->addChild("id", $this->projectId);
        $itemsXml->addChild("name", $this->projectName);
        $itemsXml->addChild("timezone", $this->timezone);
        $handlers = $itemsXml->addChild("handlers");
        foreach ($this->handlers as $name => $model) {
            $handler = $handlers->addChild("handler");
            $handler->addAttribute("name", $name);
            $handler->addAttribute("model", $model);
        }

        $packages = $itemsXml->addChild("packages");
        foreach ($this->packages as $namespace => $name) {
            $package = $packages->addChild("package");
            $package->addAttribute("namespace", $namespace);
            $package->addAttribute("name", $name);
        }

        $loaders = $itemsXml->addChild("loaders");
        foreach ($this->loaders as $file => $method) {
            $loader = $loaders->addChild("loader");
            $loader->addAttribute("path", $file);
            $loader->addAttribute("register-method", $method);
        }

        $settings = $itemsXml->addChild("settings");
        foreach ($this->projectSettings as $section => $_settings) {
            $section = $settings->addChild("section");
            $section->addAttribute("name", $section);
            foreach ($_settings as $_setting) {
                $setting = $section->addChild("setting");


                $setting->addAttribute("name", $_setting[0]);
                $setting->addAttribute("value", $_setting[1]);
                //setSetting($name, $value, $title = false, $type = false, $possibleValues = false, $configuration = false, $description = false)
                if (isset($_setting[2]) && $_setting[2]) {
                    $setting->addAttribute("title", $_setting[2]);
                }

                if (isset($_setting[3]) && $_setting[3]) {
                    $setting->addChild("type", $_setting[3]);
                }

                if (isset($_setting[4]) && $_setting[4]) {
                    $options = $setting->addChild("options");
                    foreach ($_setting[4] as $value => $label) {
                        $option = $options->addChild("option");
                        $option->addAttribute("value", $value);
                        $option->addAttribute("label", $label);
                    }
                }

                if (isset($_setting[5]) && $_setting[5]) {
                    $setting->addAttribute("run-configuration", $_setting[5]);
                }

                if (isset($_setting[6]) && $_setting[6]) {
                    $setting->addChild("description", $_setting[6]);
                }
            }

        }


        $itemsXml->asXML($this->projectDirectory . "/application/conf/project-conf.xml");
    }

    /**
     * Generates application access matrix
     */
    private function generateConfAccess()
    {
        $itemsXml = new \SimpleXMLElement("<accessmatrix></accessmatrix>");
        $itemsXml->asXML($this->projectDirectory . "/application/conf/access-matrix.xml");
    }

    /**
     * Generates ORM configuration file
     */
    private function generateConfORM()
    {
        $source = file_get_contents($this->templtesDir . "db-schema.xml.txt");
        file_put_contents($this->projectDirectory . "/application/conf/db-schema.xml", $source);
    }

    /**
     * Generates router configuration file
     */
    private function generateConfRoute()
    {
        $source = file_get_contents($this->templtesDir . "route.xml.txt");
        file_put_contents($this->projectDirectory . "/application/conf/route.xml", $source);
    }

    /**
     * Agregates configuration generators
     */
    private function generateConf()
    {
        $this->generateConfAccess();
        $this->generateConfProject();
        $this->generateConfORM();
        $this->generateConfRoute();

    }

    /**
     * Generates application controller
     */
    private function generateController()
    {
        $source = file_get_contents($this->templtesDir . "Controller.txt");
        file_put_contents($this->projectDirectory . "/application/controllers/Controller.php", $source);
    }

    /**
     * Generates application sample layout
     */
    private function generateLayout()
    {
        $source = file_get_contents($this->templtesDir . "Layout.txt");
        file_put_contents($this->projectDirectory . "/application/layouts/PresentationLayout.php", $source);

        $source = file_get_contents($this->templtesDir . "LayoutHtml.txt");
        file_put_contents($this->projectDirectory . "/application/layouts/PresentationLayout.phtml", $source);
    }

    /**
     * Generates basic application views
     */
    private function generateViews()
    {
        $source = file_get_contents($this->templtesDir . "view-index.txt");
        file_put_contents($this->projectDirectory . "/application/views/index.phtml", $source);

        $source = file_get_contents($this->templtesDir . "view-index.txt");
        file_put_contents($this->projectDirectory . "/application/views/error.phtml", $source);
    }

    /**
     * Generates application tests
     */
    private function generateTests()
    {
        $source = file_get_contents($this->templtesDir . "Test.txt");
        file_put_contents($this->projectDirectory . "/tests/ApplicationTest.php", $source);
    }

    /**
     * Generates apache htaccess file
     */
    private function generateHtaccess()
    {
        $source = file_get_contents($this->templtesDir . "htaccess.txt");
        file_put_contents($this->projectDirectory . "/.htaccess", $source);
    }

    /**
     * Generates application tests
     */
    private function generateResources()
    {
        $source = file_get_contents($this->templtesDir . "application.css.txt");
        file_put_contents($this->projectDirectory . "/resources/application.css", $source);

        $source = file_get_contents($this->templtesDir . "application.js.txt");
        file_put_contents($this->projectDirectory . "/resources/application.js", $source);
    }

    /**
     * Configures every package in project
     */
    private function configure(){
        $project = Controller::loadProject( $this->projectDirectory );
        $project->installPackages();
    }

    /**
     * Generates project
     */
    public function generate()
    {
        $this->generateStructure();
        $this->generateLoader();
        $this->generateBootstrap();
        $this->generateIndex();
        $this->generateController();
        $this->generateLayout();
        $this->generateViews();
        $this->generateConf();
        $this->generateTests();
        $this->generateHtaccess();
        $this->generateResources();
        $this->configure();
    }
}
