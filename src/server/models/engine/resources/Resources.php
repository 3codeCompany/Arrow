<?php namespace Arrow\Models;


/**
 * Arrow resources structure
 * 
 * @version 1.0
 * @license  GNU GPL
 * @author Artur Kmera <artur.kmera@arrowplatform.org> 
 */
class Resources{

	/**
	 * Resources dir
	 * 
	 * @var String
	 */
	const RESOURCES_DIR = "/resources";
	
	/**
	 * Instance
	 * 
	 * @var Resources
	 */
	private static  $selfInstance = null;

	/**
	 * Singleton !NO_REMOTE
	 * 
	 * @return Resources
	 */
	public static function getDefault() {
		if (self :: $selfInstance == null ) {
			self :: $selfInstance = new Resources();
		}
		return self :: $selfInstance;
	}
	
	/**
	 * Sets resources project
	 */
	private function __construct(  ){
	}

    /**
     * Handle for template link:template tags
     *
     * @param String $path String Mapping path
     * @param bool $package
     * @param boolean $strict If true method will throw exception if file doesn't exists
     * @throws \Arrow\Exception
     * @return \Arrow\Models\Resource
     */
	public function getResource( $path, $package = false,$strict = true ){
        if(strpos($path, "::") !==false){
            $tmp = explode("::", $path);
            $path = $tmp[1];
            $package = $tmp[0];
        }

        if($package){
            $packages = \Arrow\Controller::$project->getPackages();
		    $resourcePath = $packages[$package]["dir"].self::RESOURCES_DIR.$path;
        }else{
            $resourcePath = \Arrow\Controller::$project->getPath().self::RESOURCES_DIR.$path;
        }


		//TODO wprowadzic resourceException
		if( $strict && !file_exists( $resourcePath ) ){
			throw new \Arrow\Exception( array(  "src" => "ArrowResources", "msg" => "Resource dosn't exist", "path" => $resourcePath ) );
		}
		
		return new Resource( $package, $resourcePath );
	}
	

	
	/**
	 * Returns project reference
	 *
	 * @return Project
	 */
	public function makeDir($path){
		$resourcePath = $this->projectReference->getPath().self::RESOURCES_DIR.$path;
		return mkdir( $resourcePath );
	}
	
}
?>
