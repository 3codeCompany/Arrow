<?php
namespace Arrow;

ob_start("ob_gzhandler");

error_reporting(E_ALL ^ E_STRICT ^ E_DEPRECATED);
ini_set("display_errors", 1);

define("ARROW_SERVER_PATH", ARROW_ROOT_PATH . "/src/server");
define("ARROW_PACKAGES_PATH", ARROW_ROOT_PATH . "/src/packages");
define("ARROW_CORE_PATH", ARROW_SERVER_PATH . "/core");
define("ARROW_UPLOADS_PATH", "./data/uploads");
define("ARROW_MODELS_PATH", ARROW_SERVER_PATH . "/standardModels");
define("ARROW_CONF_PATH", ARROW_SERVER_PATH . "/conf");

require(ARROW_CORE_PATH . "/Object.php");
require(ARROW_CORE_PATH . "/Logger.php");
require(ARROW_CORE_PATH . "/Exception.php");
require(ARROW_CORE_PATH . "/ConfProvider.php");
require(ARROW_CORE_PATH . "/CacheProvider.php");
require(ARROW_CORE_PATH . "/Controller.php");

require(ARROW_CORE_PATH . "/RequestContext.php");
//require(ARROW_CORE_PATH . "/StateManager.php");
require(ARROW_CORE_PATH . "/Router.php");
require(ARROW_CORE_PATH . "/ViewManager.php");

if (!defined("ARROW_APPLICATION_PATH")) {
    define("ARROW_CACHE_PATH", ARROW_ROOT_PATH . "/data/cache");
    define("ARROW_LOG_PATH", ARROW_ROOT_PATH . "/data/logs");
} else {
    define("ARROW_CACHE_PATH", ARROW_APPLICATION_PATH . "/data/cache");
    define("ARROW_LOG_PATH", ARROW_APPLICATION_PATH . "/data/logs");
}
Logger::log("[Main] Starting..");








