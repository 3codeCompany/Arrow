<?php
namespace Arrow\CMS;

class CMSBanner extends \Arrow\ORM\ORM_Arrow_Package_CMS_CMSBanner{

	//*USER AREA*//


	
	public function delete(){
		if(file_exists($this->getImage()))
			unlink($this->getImage());
		parent::delete();
	}
	
	public function getImage(){
		return ARROW_CACHE_PATH.'/images/banner'.$this["id"].".png";
	}
	
	public function getLink(){
		return $this["link"];
	}
	
	public function genCacheFile(){
		// Path to our font file
		$font = 'arialbd.ttf';

		// Create image
		//$image = imagecreatetruecolor($width,$height);
		MediaApi::prepareMedia(array($this));
		$image = imagecreatefromjpeg($this["media"]["main"][0]["path"]);
		
		// pick color for the text
		$fontcolor = imagecolorallocate($image, 255, 255, 255);
		$x = 22; 
		imagettftext($image, 18, 0, $x, 60, $fontcolor, $font,  $this[self::F_TITLE] );
		imagettftext($image, 14, 0, $x, 90, $fontcolor, $font, $this[self::F_TEXT]);
		
		// tell the browser that the content is an image
		header('Content-type: image/png');
		// output image to the browser
		imagepng($image, ARROW_CACHE_PATH.'/images/banner'.$this["id"].".png");
		
		// delete the image resource 

		
	}

	//*END OF USER AREA*//
}
?>