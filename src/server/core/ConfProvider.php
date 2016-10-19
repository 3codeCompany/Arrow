<?php
namespace Arrow;

interface ICacheable{
    public function generateCache( $parameters );

}

/**
 * Arrow configuration provider
 *
 * @version 1.0
 * @license  GNU GPL
 * @author Artur Kmera <artur.kmera@arrowplatform.org>
 */
class ConfProvider extends Object implements ICacheable{

	const TEMPLATE_PARSER = "templateParsers-list.xml";
	/**
	 * Configuration array
	 *
	 * @var array
	 */
	private $conf;
	
	/**
	 * Self instance
	 * @var ConfProvider
	 */
	private static $oInstance = null; 
	
	/**
	 * Returns ConfProvider default instance
	 * 
	 * @return ConfProvider
	 */
	public static function getDefault(){
		if( self::$oInstance == null ){
			self::$oInstance = new ConfProvider( );
		}
		return self::$oInstance;		
	}

	private function __construct(){
		$this->conf = \Arrow\CacheProvider::getFileCache( $this, ARROW_CONF_PATH."/".self::TEMPLATE_PARSER, array( "file_prefix" => "projects_list" ) );
	}
	
	
	/**
	 * Returns configuraction document
	 *
	 * @var string document name
	 * @return SimpleXmlObject
	 */
	public function getConf( $document ){
		return $this->conf[ $document ];
	}


	public  function generateCache($parameters ){
		$big = array();
		$big[self::TEMPLATE_PARSER] = $this->compileConf( self::TEMPLATE_PARSER );
		return $big;
	}

	/**
	 * Generates arrays from xml for cache provider
	 *
	 * @param string $document Name of document ( usulaly ConfProvider::PROJECTS_LIST etc )
	 * @param SimpleXmlObject $xmlDocument
	 * @return array
	 */
	private  function compileConf( $document  ){
		$resultArray = array();
		switch ($document) {
			/**
			 * Generation of template parser reference list
			 */	
			case self::TEMPLATE_PARSER:
				$xmlDocument = simplexml_load_file( ARROW_CONF_PATH."/".self::TEMPLATE_PARSER );
				foreach( $xmlDocument->parser as $parser ){
					$model = (string)$parser["model"];
					$method = (string)$parser["method"];
					if( isset($parser["block"]) ){
						$type = "block";
						$tag =  explode( ":", (string)$parser["block"] );
					}else{
						$type = "tag";
						$tag =  explode( ":", (string)$parser["tag"] );
					}
					$resultArray[] = array( "model" => $model, "method" => $method, "type" => $type, "tag" => $tag );
				}
				break;
				
			default:
				throw new \Arrow\Exception("Wrong configuration document supplyied: ['$document']");
				break;
		}
		return $resultArray;

	}
}

?>