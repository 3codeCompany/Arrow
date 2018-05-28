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
        Router::setupAction();
        ConfigProvider::init();

        self::$project = new \Arrow\Models\Project(ARROW_APPLICATION_PATH);

    }

    public static function processCall(){

        //paths for server only

        Router::$INDEX_FILE = basename(__FILE__);
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
     * redirects to next template after bean has been executed
     *
     */
    public static function processToView()
    {
        //todo - przekierowanie na nowy addres po wykonaniu akcji
        self::rollBackRequest();
        exit();


        Logger::log("[\Arrow\Controller] Redirecting to View after bean execucion");
        $request = RequestContext::getDefault();
        $input = array_merge($request->getGet(), $request->getPostToGet(), $request->getRegistredUrlVars());
        $link = Router::$INDEX_FILE . "?";
        foreach ($input as $name => $val) {
            if ($name != Router::ACTION_BEAN_PARAMETER)
                $link .= $name . "=" . $val . "&";
        }
        $link = substr($link, 0, -1); //delete last &
        header("Location: " . $link);
        self::end();
    }

    /**
     * redirects to next template
     *
     */
    public static function redirectToView( Action $view, $get = [] )
    {
        \Arrow\Logger::log("[\Arrow\Controller] Redirecting to View after bean execucion");
        $link = Router::generateLinkFromObject( $view );
        if(!empty($_SERVER["QUERY_STRING"])){
            $link.="?".$_SERVER["QUERY_STRING"].\http_build_query ($get);
        }elseif($get){
            $link.="?".\http_build_query ($get);
        }


        header("Location: http://".$_SERVER["HTTP_HOST"]. $link );
        self::end();
    }

    /**
     * redirect to rewrite (static) address with optional parameters (query string)
     *
     */
    public static function redirectToStaticAddress($address, $addVars = true)
    {
        \Arrow\Logger::log("[\Arrow\Controller] Redirecting to static address: " . $address);

        $request = RequestContext::getDefault();
        $input = array_merge($request->getPostToGet(), $request->getRegistredUrlVars());

        $link = $address;

        if ($addVars) {
            if (strpos($link, "?") === false)
                $link .= "?";
            else
                $link .= "&";

            foreach ($input as $name => $val) {
                if ($name != Router::ACTION_BEAN_PARAMETER && $name != Router::TEMPLATE_PARAMETER)
                    $link .= $name . "=" . $val . "&";
            }
            $link = substr($link, 0, -1); //delete last &
        }

        header("Location: " . $link);
        self::end(0);
    }

    public static function redirectToTemplate($template, $vars = [] )
    {
        $template_descriptor =  \Arrow\Models\Dispatcher::getDefault()->get($template);
        \Arrow\Controller::redirectToView($template_descriptor, $vars);
    }

    /**
     * Rollback request
     *
     */
    public static function rollBackRequest()
    {

        $link = "";
        if(isset($_SERVER["HTTP_REFERER"])){
            header("Location: " . $_SERVER["HTTP_REFERER"] . $link);
        }else{
            //todo cos zrobic z sytuacja keud
            //header("Location: " . Router::getBasePath(). "admin");
            //exit();
        }
        self::end();
    }

    /**
     * @return \Arrow\Models\Project
     */
    public static function getProject()
    {
        return self::$project;
    }

    public static function getRelativePath($from, $to) {

        $patha = explode(DIRECTORY_SEPARATOR, $from);
        $pathb = explode(DIRECTORY_SEPARATOR, $to);
        $start_point = count(array_intersect($patha,$pathb));
        while($start_point--) {
            array_shift($patha);
            array_shift($pathb);
        }
        $output = "";
        if(($back_count = count($patha))) {
            while($back_count--) {
                $output .= "..".DIRECTORY_SEPARATOR;
            }
        } else {
            $output .= '.'.DIRECTORY_SEPARATOR;
        }

        return str_replace( DIRECTORY_SEPARATOR, "/", $output . implode(DIRECTORY_SEPARATOR, $pathb));
    }


    public static function getRunConfiguration(){
        if(self::isInCLIMode())
            return "_console";
        $host = $_SERVER["HTTP_HOST"];
        if(strpos($host,"www.") === 0)
            $host = str_replace( "www.", "", $host );

        return $host;
    }

    public static function loadProject( $path ){
        return new \Arrow\Models\Project($path);
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
