<?php
namespace Arrow;
/**
 * Arrow request context
 *
 * @version  1.0
 * @license  GNU GPL
 * @author   Artur Kmera <artur.kmera@arrowplatform.org>
 * @todo     Rozwinoc o ciastka i pliki, dodac wykrywanie typu wywołania
 */
class RequestContext extends \Arrow\Object implements \ArrayAccess
{

    /**
     * Get vars
     *
     * @var array
     */
    private $get = array();

    /**
     * Post vars
     *
     * @var array
     */
    private $post;


    /**
     * Parameters prepared by external processing for action
     *
     * @var array
     */
    private $parameters = array();



    /**
     * Request instance
     *
     * @var RequestContext
     */
    private static $oInstance = null;

    private static $protocol = null;


    /**
     * Singleton
     *
     * @return RequestContext
     */
    public static function getDefault()
    {
        if (self::$oInstance == null) {
            self::$oInstance = new RequestContext(null);
        }
        return self::$oInstance;
    }

    /**
     * Returns default context request var
     *
     *
     * @param $var string
     * @return
     */
    public static function get($var)
    {
        return self::getDefault()[$var];
    }

    /**
     * Constructor
     *
     */
    public function __construct($data = null)
    {
        if ($data == null) {
            $this->post = $this->inputFilter($_POST);
            $this->get = $this->inputFilter($_GET);
        } else {
            //todo ugogolnic
            $this->get = $data;
        }
    }


    /**
     * Returns get values
     *
     * @param String $variableName
     *
     * @return mixed
     */
    public function getGet($variableName = false)
    {

        if ($variableName != false) {
            if (isset($this->get[$variableName])) {
                return $this->get[$variableName];
            } else {
                //throw new \Arrow\Exception( "[Request] Get var is not set: '$variableName'", 1 );
                return false;
            }
        }
        return $this->get;
    }

    /**
     * Return post var
     *
     * @param String $variableName
     *
     * @return mixed
     */
    public function getPost($variableName = false)
    {
        if ($variableName != false) {
            if (isset($this->post[$variableName])) {
                return $this->post[$variableName];
            } else {
                //throw new \Arrow\Exception( "[Request] Post var is not set: '$variableName'", 2 );
                return false;
            }
        }
        return $this->post;
    }

    /**
     * Return request var
     *
     * @param String $variableName
     *
     * @return mixed
     */
    public function getRequest($variableName = false)
    {
        if ($variableName === false) {
            return array_merge($this->getPost(), $this->getGet(), $this->parameters);
        }
        $value = $this->getGet($variableName);
        if ($value === false)
            $value = $this->getPost($variableName);

        if ($value === false)
            $value = $this->getParameter($variableName);

        return $value;
    }


    /**
     * Filter for input values
     *
     * @param $inputArray Array
     *
     * @return Array filtered values in array
     * @todo implement filter
     */
    private function inputFilter($inputArray)
    {
        if (get_magic_quotes_gpc()) {
            foreach ($inputArray as $key => $val) {
                if (is_array($val)) {
                    $inputArray[$key] = $this->inputFilter($val);
                } else {
                    $inputArray[$key] = stripslashes($val);
                }
            }
        }
        return $inputArray;
    }

    public static function getProtocol(){
        if(self::$protocol == null) {
            if ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER["SERVER_PORT"] != 80) {
                self::$protocol =  "https://";
            }else {
                self::$protocol = "http://";
            }
        }

        return self::$protocol;
    }

    /**
     * @param array $parameters
     */
    public function addParameter($name,$value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * @return array
     */
    public function getParameter($name)
    {
        return isset($this->parameters[$name])?$this->parameters[$name]:false;
    }


    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }



    public function isXHR()
    {

        if(isset($_REQUEST["__ARROW_FORCE_AJAX__"]))
            return true;

        if(isset($_REQUEST["jsonpCallback"]))
            return true;

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']))
            return @$_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

    }


    /**
     * ArrayAcces implementaction
     *
     * @param string $key
     *
     * @return boolean
     */
    public function offsetExists($key)
    {
        if (isset ($this->get[$key]) || isset ($this->post[$key]) || isset ($this->parameters[$key])) {
            return true;
        }
        return false;
    }

    /**
     * ArrayAcces implementaction
     *
     * @param string $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        if ($this->offsetExists($key)) {
            return $this->getRequest($key);
        } else {
            return false;
            throw new \Arrow\Exception(array("msg" => "[RequestContext] Offset doesn't exist'", "offset" => $key), 0);
        }
    }

    /**
     * ArrayAcces implementaction
     *
     * @param string $key
     * @param        mixed value
     */
    public function offsetSet($key, $value)
    {
        throw new \Arrow\Exception(array("msg" => "[RequestContext] Can't use Array acces to set  value'"), 0);
    }

    /**
     * ArrayAcces implementaction
     *
     * @param string $key
     *
     * @return mixed
     */
    public function offsetUnset($key)
    {
        throw new \Arrow\Exception(array("msg" => "[RequestContext]  Can't use Array acces to unset  value'"), 0);
    }

    public static function getBaseUrl(){
        return RequestContext::getProtocol().$_SERVER["HTTP_HOST"].Router::getBasePath();
    }

    public static function getCurrentUrl() {
        $pageURL = 'http';
        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }



}

?>
