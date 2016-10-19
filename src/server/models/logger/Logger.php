<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 09.02.14
 * Time: 07:50
 */

namespace Arrow\Models\Logger;


abstract class  Stream{

    abstract function write($text, $title);


}

class FileStream extends Stream{
    private $file;
    const SAVE_DATA_DIR = "./data/history/";
    public function __construct(){
        $tmp = explode("-", $context["date"]);
        $file  = self::SAVE_DATA_DIR.$tmp[0]."-".$tmp[1]."/".$context["id"].".txt";
    }


    public function write($text,$title){
        file_put_contents( $this->file, $text, FILE_APPEND );
    }
}

class StdOutputStream  extends Stream{
    public function write($text,$title){
        echo $text;
    }
}

class ConsoleStream extends Stream{
    public function write($text,$title ){
        \PhpConsole\Connector::getInstance()->getDebugDispatcher()->dispatchDebug($text,$title);
    }
}

class Logger {


    private static $instances = false;

    /**
     * @var Stream[]
     */
    private $streams = [];


    /**
     * @param bool $name
     * @param Stream $stream
     * @return Logger
     */
    public static function get( $name = "__default__", Stream $stream = null){

        if( !self::$instances){
            $inst  = new Logger();
            if($stream)
                $inst->addStream($stream);
            else{
                $inst->addStream(new StdOutputStream());
            }

            self::$instances = [ $name =>  $inst ];

        }

        return self::$instances[$name];


    }

    public function addStream(Stream $stream){
        $this->streams[] = $stream;
    }

    public function log( $text, $title = false){
        foreach($this->streams as $stream)
            $stream->write($text, $title );

    }

} 