<?php
namespace Arrow\Models;

use Arrow\Exception;

/**
 * ErrorHandler - onvert error to exception
 * @access public
 * @package ErrorHandler
 * @author 3code group
 */
class ErrorHandler
{
    /**
     * Object instance keeper
     *
     * @var ErrorHandler
     */
    private static $oInstance = null;

    /**
     * Singleton
     *
     * @return ErrorHandler
     */
    public static function getDefault()
    {
        if (self::$oInstance == null) {
            self::$oInstance = new ErrorHandler();
        }
        return self::$oInstance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     * @param int $err_no
     * @param string $err_msg
     * @param string $err_file
     * @param int $err_line
     * @return void
     */
    public function raiseError($err_no, $err_msg, $err_file, $err_line)
    {
        if (error_reporting() != 0 && error_reporting() & ($err_no == $err_no) && $err_no != 8192 /*&& $err_no != 2048*/) {
            //0 - jak jest @ to == 0 // 2048 - warning about timezone settings
            //` " . $err_file . ":" . $err_line
            $exception = new \Exception($err_msg, 0);



            throw $exception;
        }
        return true;
    }
}

?>
