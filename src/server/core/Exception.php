<?php
namespace Arrow;
/**
 * Arrow exception
 *
 * @license  GNU GPL
 * @author   Artur Kmera <artur.kmera@arrowplatform.org>
 */

class Exception extends \Exception {

    private $data = null;

    /**
     * @var ExceptionContent
     */
    private $content;

	public function __construct($errorData, $errorCode = 0, Exception $previous = null) {
        $message = $errorData;
		if( is_array($errorData) ){
            if(isset($errorData["msg"]))
                $message = $errorData["msg"];//.print_r($errorData,1);
            unset($errorData["msg"]);
            $this->data = $errorData;
        }elseif( $errorData instanceof \Arrow\Models\ExceptionContent ){
            $this->content  = $errorData;
            $message = $this->content->getMessage();
        }

        parent::__construct($message, $errorCode, $previous);
	}

    public function getData(){
        if(empty($this->data) && !empty($this->content))
            return $this->content->getParameters();
        return $this->data;
    }


	
	public function getMessageArray(){
		return  unserialize(parent::getMessage());
	}

    /**
     * @return ExceptionContent
     */
    public function getContent(){
        return $this->content;
    }

}
?>