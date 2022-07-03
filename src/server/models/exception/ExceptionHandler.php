<?php namespace Arrow\Models;

use Arrow\Access\Models\Auth;
use Arrow\Exception;
use Arrow\Kernel;
use Arrow\Router;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\ErrorHandler\DebugClassLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class ExceptionHandler
{
    const DISPLAY = "display";
    const REDIRECT = "redirect";
    public $clearOutput = true;
    /**
     * Object instance keeper
     *
     * @var ExceptionHandler
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
        set_exception_handler([$this, "handleException"]);
        register_shutdown_function([&$this, "fatalHandler"]);
        error_reporting(E_ALL ^ E_STRICT);
        ini_set("display_errors", 1);
        set_error_handler([$this, "raiseError"]);
    }

    /**
     * @param int $err_no
     * @param string $err_msg
     * @param string $err_file
     * @param int $err_line
     * @return void
     */
    public function raiseError($severity, $message, $file, $line)
    {
        if (
            error_reporting() != 0 &&
            error_reporting() & ($severity == $severity) &&
            $severity != 8192 /*&& $err_no != 2048*/
        ) {
            //0 - jak jest @ to == 0 // 2048 - warning about timezone settings
            //` " . $err_file . ":" . $err_line
            throw new \ErrorException($message, 0, $severity, $file, $line);

            throw $exception;
        }
        return true;
    }

    public function fatalHandler()
    {
        $error = error_get_last();
        $url = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] : 'cli';

        if ($error !== null && $url !== "cli") {
            $logger = new Logger('error_logger');
            $formatter = new JsonFormatter();
            $formatter->includeStacktraces(true);
            $handler = new StreamHandler('php://stdout', Logger::INFO);
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);

            $auth = Auth::getDefault();
            $toLog = [
                "file" => $error["file"],
                "line" => $error["line"],
                "user" => $auth->isLogged() ? $auth->getUser()->_login() : "---",
                "message" => $error["message"],
                "url" => $url
            ];

            $logger->error($error["message"], $toLog);
        }
    }

    public function handleException($exception)
    {
        $hash = md5(microtime() . rand(5000, 100000));

        $this->logError($exception, $hash);

        $cli = \Arrow\Kernel::isInCLIMode();

        if (!$cli && false) {
            header("X-Arrow-Error: 1");
        }

        if ($this->clearOutput && !$cli) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            http_response_code(500);
            ob_start("ob_gzhandler");
        }

        if (  false && !isset($_ENV["APP_ENV"]) || isset($_ENV["APP_ENV"])  && $_ENV["APP_ENV"] !== "dev"  ) {
            print $this->printPublicMinimumMessage($hash);
        } else {
            $request = Kernel::getProject()
                ->getContainer()
                ->get(Request::class);
            if ($request->isXmlHttpRequest()) {
                print json_encode([
                    "__arrowException" => [
                        "msg" => $exception->getMessage(),
                        "line" => $exception->getLine(),
                        "file" => $exception->getFile(),
                        "code" => $exception->getCode(),
                        "trace" => $exception->getTraceAsString(),
                        "parameters" => $exception instanceof Exception ? $exception->getData() : []
                    ]
                ]);
            } else {
                print $this->getHead();
                print $this->printDeveloperMessage($exception, $hash);
                print $this->getFooter();
            }
        }
        exit();
    }

    private function logError(\Throwable $exception, $hash)
    {
        $logger = new Logger('error_logger');
        $formatter = new JsonFormatter();
        $formatter->includeStacktraces(true);
        $handler = new StreamHandler('php://stdout', Logger::INFO);
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        $auth = Auth::getDefault();
        $toLog = [
            "id" => $hash,
            "user" => $auth->isLogged() ? $auth->getUser()->_login() : "---",
            "file" => $exception->getFile(),
            "line" => $exception->getLine(),
            "url" => isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] : 'cli',
            "exception" => $exception
        ];

        if (strpos($toLog["file"], "standardHandlers/ErrorHandler") !== false) {
            $trace = $exception->getTrace();
            if ($trace[0]["function"] == "raiseError") {
                array_shift($trace);
            }
            $toLog["file"] = $trace[0]["file"];
            $toLog["line"] = $trace[0]["line"];
        }

        $logger->error($exception->getMessage(), $toLog);
    }

    private function printDeveloperMessage($exception, $hash)
    {
        $file = $exception->getFile();
        $line = $exception->getLine();

        $str = "<div class='red big'>Message</div>";

        if ($exception instanceof \Arrow\Exception) {
            $arr = $exception->getData();
            $str .= "<div class='white big'>";
            $str .= "{$exception->getMessage()}";
            unset($arr["msg"]);

            if (!empty($arr)) {
                $str .= "<pre>" . print_r($arr, 1) . "</pre>";
            }
            $str .= "</div>";
        } else {
            $str .= "<div class='white big'>" . $exception->getMessage() . '</div>';
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

        $str .= "<div class='red big'>File</div>";

        $str .= "<div class='white big'>" . $file . ":" . $line . "</div>";

        $str .= "<div class='red big'>Source</div>";

        $str .= "<div class='white normal'><pre>" . $this->_highlightSource($file, $line, 20) . "</pre></div>";

        $str .= "<div class='red big'>Request</div>";
        $str .= "<div class='white normal'>" . $this->printRequest() . "</div>";
        $str .= "<div class='red big'>Stacktrace</div>";
        $str .= "<div class='white normal'>" . $this->stackTrace($exception) . "</div>";

        return $str;
    }

    private function printPublicMinimumMessage($hash)
    {
        $date = date("Y-m-d H:i:s");

        $errstr = $this->getHead();
        $errstr .=
            "<h1>Internal Server Error</h1>
                        An internal error occurred while the Web server was processing your request.<br />
                        Please contact the webmaster to report this problem. <br />
                        Thank you.
                        <hr />" .
            $date .
            "<br />" .
            $hash;
        $errstr .= $this->getFooter();

        return $errstr;
    }

    private function printRequest()
    {
        if (!isset($_SERVER["REQUEST_URI"])) {
            return false;
        }

        $str = "<table style='width: 100%;'>";

        /** @var Request $request */
        $request = Kernel::getProject()
            ->getContainer()
            ->get(Request::class);

        $str .=
            "<tr><td>[URL]</td><td><pre>" .
            $request->getSchemeAndHttpHost() .
            "" .
            $request->getRequestUri() .
            "</pre></td></tr>";

        $str .= "<tr><td colspan='2'><b>Request:</b></td></tr>";
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
        $lines = str_replace([ "\n\r", "\r\n", "\n", "\r"], "<br />", $lines);

        $lines = explode("<br />", $lines);

        $offset = max(0, $lineNumber - ceil($showLines / 2));
        $lines = array_slice($lines, $offset+1, $showLines);

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
        if ($i == 0 || empty($args)) {
            return "";
        }
        $str = "";
        foreach ($args as $key => $arg) {
            $str .= "<br />";
            if (is_object($arg)) {
                $str .= get_class($arg); //." ".$arg->__toString();
            } elseif (is_numeric($arg)) {
                $str .= $arg;
            } elseif (is_bool($arg)) {
                $str .= $arg ? "true" : "false";
            } elseif (is_array($arg)) {
                $str .= "Array[" . count($arg) . "] {" . $this->printArguments($arg, $i) . "}";
            } elseif (is_string($arg)) {
                $str .= substr($arg, 0, 100);
            }
            //if ($key + 1 < count($args)) {
            $str .= ", ";
            //}
        }
        return $str . "<br />";
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
        $html = '<div class="stacktrace">';

        $html .= "<table style='width: 100%'>";
        foreach ($exception->getTrace() as $key => $trace) {
            $reference = isset($trace["class"]) ? $trace["class"] . "::" . $trace["function"] : "" . $trace["function"];
            if (strpos($reference, "ErrorHandler::raiseError") !== false) {
                continue;
            }
            $html .= "<tr>";
            $html .= "<td>" . $reference . "</td>";
            $html .= '<td>(' . (isset($trace["args"]) ? $this->printArguments($trace["args"]) : '') . ' )</td>';
            if (!isset($trace["file"])) {
                $html .= '<td class="file">---</td></tr>';
                continue;
            }
            $html .=
                '
                <td class="file">
                    ' .
                $trace['file'] .
                ' Line: ' .
                $trace['line'] .
                '</td>';
            $html .= "</tr>";
        }
        $html .= "<table>";
        $html .= '</div>';
        return $html;
    }

    private function getHead()
    {
        $head = <<<HEAD
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
   		width: 1200px;
   		margin: 10px auto; 
   		background-color: lightgrey;
	}
	body > div{
	    background-color: #f3f1f1;
	}
	table  td{
	    vertical-align: top;
	    padding: 8px;
	}
	table  tr:nth-child(even){
	    background-color: lightgrey;
	}
	.source{
	    display: none;
	}
	a{
	    text-decoration:none;
	 }
	.file{
	    font-size:11px;
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
	
	pre{
	    padding: 0px;
	    margin: 0;
	    font-size: 13px;
	}
	
	.white{
	    background-color: white; 
	    color: black;
	}
	
	
	.red{
	    background-color: #650101; 
	    color: white;
	}
	.big{
	    font-size: 20px;  padding: 10px;
	}
	.stacktrace{
	    font-size: 14px;
	}

 
   	</style>
</head><body><div>
HEAD;
        return str_replace(["\n", "\t"], "", $head);
    }

    private function getFooter()
    {
        $footer = "</div></body></html>";
        return $footer;
    }
}

?>
