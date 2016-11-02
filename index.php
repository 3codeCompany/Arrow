<?php

set_time_limit(300);
error_reporting(E_ALL ^ E_STRICT ^ E_DEPRECATED);
ini_set("display_errors", 1);

require_once "vendor/autoload.php";


define( "ARROW_APPLICATION_PATH", realpath(__DIR__) );
define("ARROW_CACHE_PATH", ARROW_APPLICATION_PATH . "/data/cache");
define("ARROW_LOG_PATH", ARROW_APPLICATION_PATH . "/data/logs");
\Arrow\Controller::init();

\Arrow\Controller::processCall();