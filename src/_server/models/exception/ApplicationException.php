<?php namespace Arrow\Models;

    /**
 * Created by JetBrains PhpStorm.
 * User: Artur
 * Date: 14.10.12
 * Time: 15:23
 * To change this template use File | Settings | File Templates.
 */

class ApplicationException extends \Exception{

    /**
     * @var ExceptionContent
     */
    private $content;

    public function __construct($message, $code = 0, Exception $previous = null)
    {
        if(is_string($message))
            $message = new ExceptionContent($message);

        $this->content  = $message;

        parent::__construct($message->getMessage(), $code, $previous);
    }

    /**
     * @return ExceptionContent
     */
    public function getContent(){
        return $this->content;
    }

}