<?php namespace Arrow\Models;


use Arrow\Router;
use Monolog\Formatter\LineFormatter;

class ExceptionHandler implements IExceptionHandler
{
    const DISPLAY = "display";
    const REDIRECT = "redirect";
    public $clearOutput = true;
    /**
     * Object instance keeper
     *
     * @var SessionHandler
     */
    private static $oInstance = null;

    /**
     * Singleton
     *
     * @return ExceptionHandler
     */
    public static function getDefault()
    {

        if (self::$oInstance == null) {
            self::$oInstance = new ExceptionHandler();
        }
        return self::$oInstance;
    }

    private function __construct()
    {
        set_exception_handler(array($this, "displayException"));
        register_shutdown_function([&$this, "fatalHandler"]);

    }

    public function fatalHandler()
    {
        $errfile = "unknown file";
        $errstr = "shutdown";
        $errno = E_CORE_ERROR;
        $errline = 0;

        $error = error_get_last();

        if ($error !== null) {
            $errno = $error["type"];
            $errfile = $error["file"];
            $errline = $error["line"];
            $errstr = $error["message"];
            $error["url"] = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] : 'cli';
            $error["request"] = $_REQUEST;

            $this->logError($error, $error["message"]);
        }
    }

    private function printDeveloperMessage(\Exception $exception)
    {

        $str = "";
        if ($exception instanceof \Arrow\Exception || $exception instanceof \ErrorException) {


            $arr = $exception->getData();
            $str .= "<h2>{$exception->getMessage()}</h2>";
            unset($arr["msg"]);


            if (!empty($arr)) {
                $str .= "<pre>" . print_r($arr, 1) . "</pre>";
            }

        } else {
            $str = print_r(get_class($exception) . " " . $exception->getMessage(), 1);
        }


        if ($prev = $exception->getPrevious()) {
            $i = 0;
            while ($prev) {
                $str .= "<div style='margin-top: 20px;'><b>Previous:</b> " . $prev->getMessage() . "</div>";
                $prev = $prev->getPrevious();

                if ($i > 15) {
                    break;
                }
                $i++;
            }
        }

        $currView = \Arrow\ViewManager::getCurrentView();
        if ($currView) {
            $str .= "<div style='margin-top: 20px;'><b>Current template:</b>  " . $currView->get()->getPackage() . "::" . $currView->get()->getPath() . "</div>";
        }

        $runConf = \Arrow\Controller::getRunConfiguration();
        //$str .= "<div style='margin-top: 20px;'><a href='http://localhost:8091/?message={$exception->getFile()}:{$exception->getLine()}' target='_blank' >Go to error</a></div>";

        $str .= "<div style='margin-top: 20px;'><b>File:</b> " . $exception->getFile() . ":" . $exception->getLine() . "</div>";
        $str .= "<div style='margin-top: 20px;'><b>Run configuration:</b> " . \Arrow\Controller::getRunConfiguration() . "</div>";

        $str .= "<div style='margin-top: 20px;'>" . $this->printRequest() . "</div>";
        $str .= "<div style='margin-top: 20px;'>" . $this->stackTrace($exception) . "</div>";

        return $str;
    }

    private function printPublicMinimumMessage()
    {
        $date = date("Y-m-d H:i:s");

        $errstr = $this->getHead();
        $errstr .= "<h1>Internal Server Error</h1>
                        An internal error occurred while the Web server was processing your request.<br />
                        Please contact the webmaster to report this problem. <br />
                        Thank you.
                        <hr />" . $date;
        $errstr .= $this->getFooter();

        return $errstr;

    }

    private function printCLIMessage()
    {

    }

    private function log($exception)
    {
        \Arrow\Logger::log($this->printDeveloperMessage($exception), \Arrow\Logger::EL_ERROR);
    }

    public function displayException($exception)
    {
        $this->log($exception);

        $cli = \Arrow\Controller::isInCLIMode();

        if ($this->clearOutput && !$cli) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            ob_start("ob_gzhandler");
        }

        if ($cli) {
            print $this->printConsoleMessage($exception);
            exit();
        }


        ob_start();
        print "<!--\n" . $exception->getMessage() . "\n" . $exception->getFile() . ":" . $exception->getLine() . "\n-->\n\n\n";
        print $this->getHead();
        print $this->printDeveloperMessage($exception);
        print $this->getFooter();
        $content = ob_get_contents();
        ob_clean();

        $this->logError($exception, $content);

        //zmienić aby było pobierane przez handlery
        $user = null;
        if (class_exists("\\Arrow\\Access\\Auth", false)) {
            $user = \Arrow\Access\Models\Auth::getDefault()->getUser();
        }


        //@todo sprawdzić co w systemie przestawia forcedisplayerrors na true ( nie wyśledzone do tej pory )
        //if (!Project::$forceDisplayErrors &&  ($user == null || !$user->isInGroup("Developers"))) {

        /*print $this->getHead().print $this->printDeveloperMessage($exception).$this->getFooter();
        exit();*/
        if (($user == null || !$user->isInGroup("Developers"))) {
            print $this->printPublicMinimumMessage();
        } elseif (\Arrow\RequestContext::getDefault()->isXHR() && $exception instanceof \Arrow\Models\ApplicationException) {
            $this->printXHRException($exception);
        } else {
            print "<!--\n" . $exception->getMessage() . "\n" . $exception->getFile() . ":" . $exception->getLine() . "\n-->\n\n\n";
            print $this->getHead();
            echo '<h1>Exception occured</h1>';
            if (!Project::$forceDisplayErrors && ($user == null || !($user->isInGroup("Developers")))) {
                print $this->printUserMessage($exception);
            } else {
                print $this->printDeveloperMessage($exception);
            }
            print $this->getFooter();
        }


        exit();
    }


    private function logError($exception, $contents)
    {
        $dir = ARROW_DOCUMENTS_ROOT . "/data/logs/errors/" . date("Y-m-d");
        $logFile = date("Y-m-d_H_i_s") . rand(1, 1000) . ".html";


        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }


        file_put_contents($dir . "/" . $logFile, $contents);
        $logger = new \Monolog\Logger('mySiteLog');

        $hipChatHandler = new \Monolog\Handler\HipChatHandler(
            "LgyH7guDV2ZJ6VDubma2wfpzXFbeYTrY69l2PnF5", "3443801", 'Monolog', true,
            \Monolog\Logger::CRITICAL, true, true, 'text',
            "esotiq.hipchat.com",
            \Monolog\Handler\HipChatHandler::API_V2
        );
        $hipChatHandler->setFormatter(new LineFormatter(null, null, true, true));

        if ($exception instanceof \Exception) {
            $message = $exception->getMessage();
            $line = $exception->getLine();
            $file = $exception->getFile();
        } else {
            $message = $exception["message"];
            $line = $exception["line"];
            $file = $exception["file"];
        }


        $logger->pushHandler($hipChatHandler);
        $logger->log(
            \Monolog\Logger::CRITICAL,
            (isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] . " \nFull url: http://" . $_SERVER["HTTP_HOST"] . Router::getBasePath() . "/data/logs/errors/" . date("Y-m-d") . "/" . $logFile : "") .
            "\n" . $message .
            "\n" . $file . ":" . $line


        );


        //@mail( "artur.kmera@3code.pl", "[ArrowError] ".$_SERVER["HTTP_HOST"], "Full url: http://".$_SERVER["HTTP_HOST"]."/data/logs/errors/" . date("Y-m-d")."/".$file."\n\n\n".$contents );

    }

    private function printConsoleMessage($exception)
    {
        print $exception->getMessage();
        if (method_exists($exception, "getState")) {
            print implode("\n", $exception->getData());
        }
    }

    private function printUserMessage($exception)
    {
        print "<br />Server problem please contact with administrator";
        exit;
    }

    private function printXHRException($exception)
    {
        $ret = array("exception" => array("message" => $exception->getMessage(), "parameters" => $exception->getContent()->getParameters()));
        print json_encode($ret);
        exit;
    }

    private function printRequest()
    {
        if (!isset($_SERVER["REQUEST_URI"])) {
            return false;
        }

        $refresh = '<form  action="' . $_SERVER["REQUEST_URI"] . '" method="post" __ARROW_TARGET__ >';
        foreach ($_REQUEST as $var => $value) {

            if (!is_array($value)) {
                $refresh .= '<input type="hidden" name="' . $var . '" value="' . $value . '">';
            } else {
                foreach ($value as $index => $indexVal) {
                    //$refresh .= '<input type="hidden" name="' . $var . '[' . $index . ']" value="' . $indexVal . '">';
                }
            }
        }
        $refresh .= '<input type="submit" value="request again __ARROW_SUBMIT__" style="float: left;" />';
        $refresh .= '</form>';

        $refreshNewWindow = str_replace(array("__ARROW_TARGET__", "__ARROW_SUBMIT__"), array('target="_blank"', "[new window]"), $refresh);
        $refresh = str_replace(array("__ARROW_TARGET__", "__ARROW_SUBMIT__"), array("", ""), $refresh);


        $str = "<h3><div style='float: left;'> Request</div> {$refresh}  {$refreshNewWindow}</h3><div style='clear: both;'></div><table>";

        $str .= "<tr><td>[URL]</td><td><pre>" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "</pre></td></tr>";

        foreach ($_REQUEST as $var => $value) {
            $str .= "<tr><td>{$var}</td><td><pre>" . print_r($value, 1) . "</pre></td></tr>";
        }
        $str .= "</table>";
        return $str;


    }

    /**
     * Retrieve the relevant portion of the PHP source file with syntax highlighting
     *
     * @param string $fileName The full path and filename to the source file
     * @param int $lineNumber The line number which to highlight
     * @param int $showLines The number of surrounding lines to include as well
     */
    protected function _highlightSource($fileName, $lineNumber, $showLines)
    {
        $lines = file_get_contents($fileName);
        $lines = highlight_string($lines, true);
        $lines = explode("<br />", $lines);

        $offset = max(0, $lineNumber - ceil($showLines / 2));

        $lines = array_slice($lines, $offset, $showLines);

        $html = '';
        foreach ($lines as $line) {
            $offset++;
            $line = '<em class="lineno">' . sprintf('%4d', $offset) . ' </em>' . $line . '<br/>';
            if ($offset == $lineNumber) {
                $html .= '<div style="background: #ffc">' . $line . '</div>';
            } else {
                $html .= $line;
            }
        }

        return $html;
    }

    public function printArguments($args, $i = 3)
    {
        $i--;
        if ($i == 0) {
            return "";
        }
        $str = "";
        foreach ($args as $key => $arg) {
            if (is_object($arg)) {
                $str .= get_class($arg); //." ".$arg->__toString();
            } elseif (is_array($arg)) {
                $str .= "Array[" . count($arg) . "] {" . $this->printArguments($arg, $i) . "}";
            } elseif (is_string($arg)) {
                $str .= substr($arg, 0, 20);
            }
            if ($key + 1 < count($args)) {
                $str .= ", ";
            }
        }
        return $str;
    }


    /**
     *
     * Print the stack Trace
     *
     * @param Exception $exception Any kind of exception
     * @param int $showLines Number of surrounding lines to display (optional; defaults to 10)
     */
    public function stackTrace($exception, $showLines = 10)
    {
        $html = '<style type="text/css">'
            . '.stacktrace p { margin: 0; padding: 0; }'
            . '.source { border: 1px solid #000; overflow: auto; background: #fff;'
            . ' font-family: monospace; font-size: 12px; margin: 0 0 25px 0 }'
            . '.lineno { color: #333; }'
            . '</style>'
            . '<div class="stacktrace">'
            . '<div class="source">'
            . $this->_highlightSource($exception->getFile(), $exception->getLine(), $showLines)
            . '</div>';

        $html .= "<h3>Stacktrace</h3><table>";
        foreach ($exception->getTrace() as $key => $trace) {
            $html .= "<tr><td>";
            $html .= (isset($trace["class"])) ? $trace["class"] . "::" . $trace["function"] : "" . $trace["function"];
            $html .= '( ' . (isset($trace["args"]) ? $this->printArguments($trace["args"]) : '') . ' )</td>';
            if (!isset($trace["file"])) {
                $html .= '<td class="file">---</td></tr>';
                continue;
            }
            $html .= '<td class="file"><a href="" onclick="document.getElementById(\'source' . $key . '\').style.display=\'block\'; return false;" >File: ' . $trace['file'] . ' Line: ' . $trace['line'] . '</a></td>';
            $html .= "</tr>";
            $html .= "<tr>";
            $html .= '<td colspan="2"><div class="source" id="source' . $key . '">'
                . $this->_highlightSource($trace['file'], $trace['line'], 5)
                . '</div></td>';
            $html .= "</tr>";
        }
        $html .= "<table>";
        $html .= '</div>';
        return $html;
    }


    private function getHead()
    {
        $head =
            <<<HEAD
            	<!--Error--><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
   	<title>Arrow Error</title>
   	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
   	<style type="text/css" >
   	body {
   		background-color: white;
   		font-size:12px;
   		font-family:"Verdana";
	}
	.source{
	    display: none;
	}
	a{
	    text-decoration:none;
	 }
	.file{
	    font-size:9px;
	}
	h1{
		color:red;
		font-size:18pt;
		font-weight:normal;
	}
	
	h2{
		color:maroon;
		font-family:"Verdana";
		font-size:14pt;
		font-weight:normal;
	}
	
	legend{
		font-size: 14px;
		color:maroon;
		font-weight: bold;
		padding: 4px;
	}

   	</style>
</head><body>
HEAD;
        return str_replace(array("\n", "\t"), "", $head);
    }

    private
    function getFooter()
    {
        $footer = "</body></html>";
        return $footer;
    }
}

?>