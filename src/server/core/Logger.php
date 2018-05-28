<?php namespace Arrow;
/**
 Iterface with let you redefine the save function.
 */
interface ILoggerSave {
	/**
	@param string $current_time - hh:mm:ss dd:mm:YYYY (np: 13:33:33 12:01:2008)
	@param string $message - is informaction which is delegate to log
	@return string - zwraca informaction to save in a file
	*/
	public function createMessage( $current_time, $message ) ;
}

/**
 Uniwersal class which let you log data
 */
class Logger  implements ILoggerSave {

	/**
	 Defined constant
	 */

	const EL_ERROR = 1;
	const EL_EXCEPTION = 2;
	const EL_WARNING = 2;
	const EL_INFO = 3;

	private static $url = "/__DATE___arrowlog.html";    // path to file place where I can made file
	private static $secondurl = "/__DATE___arrowlog.txt";
	
	private static $instance = null;         // instant of global logger
	
	protected $hFile = null;                  // handler to logger file
	protected $hSecondFile = null ;
	protected $levels = null;                        // array of log with are adds to file
	protected $path = "" ;
	protected $saver;                         // Object which prepare data to save

	/** Create a object - create or open log file
	@param string $path - path to place where we want to have log file e.g. "log.txt" or "../../folder/file_log.xml"
	@param ISaveLogger $saver - object which implements interface IsaveLogger

	if you use in the path special word 'DATE' for this word will be put curent date
	*/
	public function __construct( $path, $saver = null ) {
		$this->path = $path ;
		$this->levels = array( self::EL_ERROR, self::EL_EXCEPTION, self::EL_WARNING/*, self::EL_INFO */);
		if( $saver == null )  $this->saver = $this;
		else $this->saver = $saver;
	}

	/**
	 Destructor - close log file
	 */
	public function __destruct() {
		if(!empty($this->hFile) ) 
			fclose( $this->hFile );
		if(!empty($this->hSecondFile) ) 
			fclose( $this->hSecondFile );
	}

	/**
	 Add level which will be logged
	 @param int $level - level which do you want to logg e.g: EL_INFO, EL_WARNING, EL_ERROR
	 @param \Arrow\Logger $logobj - logger which you use if null You use Global logger
	 */
	public static function addLoggedLevel( $level, $logobj = null ) {
		$logger = \Arrow\Logger::getObject( $logobj ) ;
		$logger->levels[$level] = $level;
	}

	/**
	 Remove level which is logged
	 @param int $level - level which do you want to logg e.g: EL_INFO, EL_WARNING, EL_ERROR
	 @param \Arrow\Logger $logobj - logger which you use if null You use Global logger
	 */
	public static function removeLoggedLevel( $level, $logobj = null ) {
		$logger = \Arrow\Logger::getObject( $logobj ) ;
		if( isset( $logger->levels[$level] ) ) unset( $logger->levels[$level] );
	}

	/**
	 Logg message
	 @param string $message - informaction which you want logg
	 @param int $level - e.g: EL_INFO, EL_WARNING, EL_ERROR
	 @param \Arrow\Logger $logobj - logger which you use if null You use Global logger
	 */
	public static function log( $message, $level = self::EL_INFO, $logobj = null ) {
		$logger = \Arrow\Logger::getObject( $logobj ) ;
		if( isset( $logger->levels[$level] ) ){
			$message = $logger->saver->createMessage( @date( "G:i:s d-m-Y" ), $message );
			$logger->writeToFile( $message ) ;
		}
	}

	/**
	 * From ISaveLogger
	 *
	 * @param string $current_time - hh:mm:ss dd:mm:YYYY (np: 13:33:33 12:01:2008)
	 * @param string $message - is informaction which is delegate to log
	 * @return string - zwraca informaction to save in a file
	 */
	public function createMessage( $current_time, $message ) {
		return $current_time." : ".$message ."\n" ;
	}

	private static function createObject() {
		self::$instance = new \Arrow\Logger( ARROW_LOG_PATH.self::$url ) ;
		self::$instance->saver = self::$instance;
		
	}
	
	private static function getObject( $logobj ) {
		if( $logobj == null ) {
			if( !self::$instance ) self::createObject();
			$logger = self::$instance;
		} else {
			$logger = $logobj;
		}
		return $logger;
	}
	
	/*write message to file*/
	private function writeToFile( $message ) {
        return;
		if( $this->hFile == null )
			$this->hFile = fopen( str_replace( "__DATE__", @date( "Y-m-d" ) , $this->path ), "a" );
		if( $this->hSecondFile == null )
			$this->hSecondFile = fopen( str_replace( "__DATE__", @date( "Y-m-d" ) , str_replace( "html", "txt", $this->path ) ), "a" );
		$time = time() ;
		$date = @date( "Y-m-d H:i:s" ) ;
		$message_html = "<h2 style=\"border-bottom:3px solid blue;\" onclick=\"document.getElementById('id_$time').style.display='block';\">$date <- kliknij mnie </h2><div id=\"id_$time\" style=\"display:none\" >$message</div>" ;	
		fwrite($this->hFile, $message_html);
		$msg = strip_tags( $message ) ;
		
		if( strpos( $msg, "Internal Server Error") !== false ) fwrite($this->hSecondFile, $date.": ".substr( $msg , 0, strpos( $msg, "Internal Server Error") )."\n");
		else fwrite($this->hSecondFile, $date.": ".$msg."\n");
	}
}


?>