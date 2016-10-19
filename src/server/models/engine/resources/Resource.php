<?php namespace Arrow\Models;
/**
 * Arrow resource class
 * 
 * @version 1.0
 * @license  GNU GPL
 * @author Artur Kmera <artur.kmera@arrowplatform.org>
 * @todo  Wprowadzic wyjatki na listowanie kiedy resource to plik itd
 */
class Resource extends \SplFileInfo{
	
	
	/**
	 * Resource path
	 * 
	 * @var String
	 */
	private $path;

    private $package;
	
	/**
	 * Constructor sets resource path 
	 * 
	 * @param Resources $listHandle List Handle
	 * @param String $path resource path
	 */
	public  function __construct( $package, $path ){
        //TODO popracowac nad sciezkami relatywnymi
		parent::__construct($path);
        $this->package = $package;
	}
	

	/**
	 * Returns resource relative path
	 *
	 * @return String
	 */
	public function getRelativePath(){



        return Project::getInstance()->toRelative($this->getRealPath());
	}
	
	/**
	 * Returns directory iterator
	 *
	 * @return Recursive\DirectoryIterator
	 */
	public function getDirectoryIterator(){
		return new \DirectoryIterator(  $this->getAbsolutePath() );
	}
	
	
	/**
	 * Returns resource contents
	 *
	 * @return String
	 */
	public function getContents(){
		return file_get_contents( $this->getAbsolutePath() );
	}
	
	
	/**
	 * Delete resource
	 *
	 */
	public function delete(){
		if( is_dir($this->getAbsolutePath()) ){
			$this->removeDir($this->getAbsolutePath());
		}else{
			unlink( $this->getAbsolutePath() );
		}
	}
	
	/**
	 * Sets resource contents
	 * 
	 * @param String $contents
	 */
	public function setContents( $contents ){
		file_put_contents( $this->getAbsolutePath(), $contents );
	}
	
	private function removeDir($directory, $empty=FALSE)	{
		if(substr($directory,-1) == '/'){
			$directory = substr($directory,0,-1);
		}
		if(!file_exists($directory) || !is_dir($directory))	{
			return FALSE;
		}elseif(!is_readable($directory)){
			return FALSE;
		}else{
			$handle = opendir($directory);
			while (FALSE !== ($item = readdir($handle))){
				if($item != '.' && $item != '..'){
					$path = $directory.'/'.$item;
					if(is_dir($path)){
						$this->removeDir($path);
					}else{
						unlink($path);
					}
				}
			}
			closedir($handle);
			if($empty == FALSE)	{
				if(!rmdir($directory)){
					return FALSE;
				}
			}
			return TRUE;
		}
	}
	
}
?>