<?php
namespace Arrow\Utils\Models\Images;
/**
 *  A class providing a set of methods for doing basic transformation to an image like resizing, rotating and flipping
 *
 *  The code is approx 18Kb in size but still heavily documented so you can easily understand every aspect of it
 *
 *
 *
 */
class ImageTransform{
	/**
	 *  Path and name of image file to transform
	 *
	 *  @var    string
	 */
	private $sourceFile = "";

	/**
	 *  Path and name of transformed image file
	 *
	 *  @var    string
	 */
	private $targetFile = "";

	/**
	 *  Available only for the {@link resize} method
	 *
	 *  Width, in pixels, to resize the image to
	 *
	 *  the property will not be taken into account if is set to -1
	 *
	 *  default is -1
	 *
	 *  @var    integer
	 */
	public $resizeToWidth = -1;

	/**
	 *  Available only for the {@link resize} method
	 *
	 *  Height, in pixels, to resize the image to
	 *
	 *  the property will not be taken into account if is set to -1
	 *
	 *  default is -1
	 *
	 *  @var    integer
	 */
	public $resizeToHeight = -1;

	/**
	 *  Available only for the {@link resize} method
	 *
	 *  while resizing, image will keep it's aspect ratio if this property is set to TRUE, and only one of the
	 *  {@link resizeToWidth} or {@link resizeToHeight} properties is set. if set to TRUE, and both
	 *  {@link resizeToWidth} or {@link resizeToHeight} properties are set, the image will be resized to maximum width/height
	 *  so that neither one of them will exceed given width/height while keeping the aspect ratio
	 *
	 *  default is TRUE
	 *
	 *  @var boolean
	 */
	public $maintainAspectRatio = true;

	/**
	 *  Available only for the {@link resize} method
	 *
	 *  image is resized only if image width/height is smaller than the values of
	 *  {@link resizeToWidth}/{@link resizeToHeight} properties
	 *
	 *  @var boolean
	 */
	public $resizeIfSmaller = false;

	/**
	 *  Available only for the {@link resize} method
	 *
	 *  image is resized only if image width/height is greater than the values of
	 *  {@link resizeToWidth}/{@link resizeToHeight} properties
	 *
	 *  @var boolean
	 */
	public $resizeIfGreater = true;

	/**
	 *  Available only for the {@link resize} method and only if the {@link targetFile}'s extension is jpg/jpeg
	 *
	 *  output quality of image (better quality means bigger file size).
	 *
	 *  range is 0 - 100
	 *
	 *  default is 65
	 *
	 *  @var integer
	 */
	public $jpegOutputQuality = 85;

	/**
	 *  what rights should the transformed file have
	 *
	 *  by default a file created by a script will have the script as owner and you would not be able to edit, modify
	 *  or delete the file. better is to leave this setting as it is
	 *
	 *  @var string
	 */
	public $chmodValue = "0777";

	/**
	 *  in case of an error read this property's value to find out what went wrong
	 *
	 *  possible error values are:
	 *
	 *      - 1:  source file could not be found
	 *      - 2:  source file can not be read
	 *      - 3:  could not write target file
	 *      - 4:  unsupported source file
	 *      - 5:  unsupported target file
	 *      - 6:  available version of GD does not support target file extension
	 *
	 *  @var integer
	 */
	var $error = 0;
	
	public function load( $sourceFile ){
		if(!file_exists($sourceFile))
			exit("\nfile not exist :".$sourceFile);
		
		$this->sourceFile = $sourceFile;
	}
	
	public function setTargetFile( $targetFile ){
		
		$this->targetFile = $targetFile;
	}

	/**
	 *  returns an image identifier representing the image obtained from sourceFile and the image's width and height
	 *
	 *  @access private
	 */
	private function create_image_from_source_file(){
		// performs some error checking first
		// if source file does not exists
		if (!file_exists($this->sourceFile)) {
			// save the error level and stop the execution of the script
			$this->error = 1;
			return false;
			// if source file is not readable
		} elseif (!is_readable($this->sourceFile)) {
			// save the error level and stop the execution of the script
			$this->error = 2;
			return false;
			// if target file is same as source file and source file is not writable
		} elseif ($this->targetFile == $this->sourceFile && !is_writable($this->sourceFile)) {
			// save the error level and stop the execution of the script
			$this->error = 3;
			return false;
			// get source file width, height and type
			// and if founds a not-supported file type
		} elseif (!list($sourceImageWidth, $sourceImageHeight, $sourceImageType) = getimagesize($this->sourceFile)) {
			// save the error level and stop the execution of the script
			$this->error = 4;
			return false;
			// if no errors so far
		} else {
			// creates an image from file using extension dependant function
			// checks for file extension
			switch ($sourceImageType) {
				// if gif
				case 1:
					// the following part gets the transparency color for a gif file
					// this code is from the PHP manual and is written by
					// fred at webblake dot net and webmaster at webnetwizard dotco dotuk, thanks!
					$fp = fopen($this->sourceFile, "rb");
					$result = fread($fp, 13);
					$colorFlag = ord(substr($result,10,1)) >> 7;
					$background = ord(substr($result,11));
					if ($colorFlag) {
						$tableSizeNeeded = ($background + 1) * 3;
						$result = fread($fp, $tableSizeNeeded);
						$this->transparentColorRed = ord(substr($result, $background * 3, 1));
						$this->transparentColorGreen = ord(substr($result, $background * 3 + 1, 1));
						$this->transparentColorBlue = ord(substr($result, $background * 3 + 2, 1));
					}
					fclose($fp);
					// -- here ends the code related to transparency handling
					// creates an image from file
					$sourceImageIdentifier = @imagecreatefromgif($this->sourceFile);
					break;
					// if jpg
				case 2:
					// creates an image from file
					$sourceImageIdentifier = @imagecreatefromjpeg($this->sourceFile);
					break;
					// if png
				case 3:
					// creates an image from file
					$sourceImageIdentifier = @imagecreatefrompng($this->sourceFile);
					break;
				default:
					// if file has an unsupported extension
					// note that we call this if the file is not gif, jpg or png even though the getimagesize function
					// handles more image types
					$this->error = 4;
					return false;
			}
		}
		// returns an image identifier representing the image obtained from sourceFile and the image's width and height
		return array($sourceImageIdentifier, $sourceImageWidth, $sourceImageHeight);
	}
	/**
	 *  Creates a target image identifier
	 *
	 *  @access private
	 */
	private function create_target_image_identifier($width, $height){
	    if($width == 0){
	        $width = 100;
            $height = 100;
        }
		// creates a blank image
		$targetImageIdentifier = @imagecreatetruecolor($width, $height);
        if(!$targetImageIdentifier) {
            ADebug::log($width);
            exit("" .$width);
            return;
        }

        $white = imagecolorallocate($targetImageIdentifier, 255, 255, 255);
        imagefill($targetImageIdentifier, 0, 0, $white);

		imagealphablending($targetImageIdentifier,false);
		imagesavealpha($targetImageIdentifier,true);
		// if we have transparency in the image
		if (isset($this->transparentColorRed) && isset($this->transparentColorGreen) && isset($this->transparentColorBlue)) {
			$transparent = imagecolorallocate($targetImageIdentifier, $this->transparentColorRed, $this->transparentColorGreen, $this->transparentColorBlue);
			imagefilledrectangle($targetImageIdentifier, 0, 0, $width, $height, $transparent);
			imagecolortransparent($targetImageIdentifier, $transparent);
		}
		// return target image identifier
		return $targetImageIdentifier;
	}
	/**
	 *  creates a new image from a given image identifier
	 *
	 *  @access private
	 */
	private function output_target_image($targetImageIdentifier){
		// get target file extension
		$targetFileExtension = strtolower(substr($this->targetFile, strrpos($this->targetFile, ".") + 1));
		// image saving process goes according to required extension
		switch ($targetFileExtension) {
			// if gif
			case "gif":
				// if gd support for this file type is not available
				if (!function_exists("imagegif")) {
					// save the error level and stop the execution of the script
					$this->error = 6;
					return false;
					// if, for some reason, file could not be created
				} elseif (@!imagegif($targetImageIdentifier, $this->targetFile)) {
					// save the error level and stop the execution of the script
					$this->error = 3;
					return false;
				}
				break;
				// if jpg
			case "jpg":
			case "jpeg":
				// if gd support for this file type is not available
				if (!function_exists("imagejpeg")) {
					// save the error level and stop the execution of the script
					$this->error = 6;
					return false;
					// if, for some reason, file could not be created
				} elseif (@!imagejpeg($targetImageIdentifier, $this->targetFile, $this->jpegOutputQuality)) {
					// save the error level and stop the execution of the script
					$this->error = 3;
					return false;
				}
				break;
			case "png":
				// if gd support for this file type is not available
				if (!function_exists("imagepng")) {
					// save the error level and stop the execution of the script
					$this->error = 6;
					return false;
					// if, for some reason, file could not be created
				} elseif (@!imagepng($targetImageIdentifier, $this->targetFile)) {
					// save the error level and stop the execution of the script
					$this->error = 3;
					return false;
				}
				// if not a supported file extension
			default:
				// save the error level and stop the execution of the script
				$this->error = 5;
				return false;
		}
		// if file was created successfully
		// chmod the file
		//chmod($this->targetFile, $this->chmodValue);
		// and return true
		return true;
	}
	/**
	 *  Resizes the image given as {@link sourceFile} and outputs the resulted image as {@link targetFile}
	 *  while following user specified properties
	 *
	 *  @return boolean     TRUE on success, FALSE on error.
	 *                      If FALSE is returned, check the {@link error} property to see what went wrong
	 */
	public function resize(){
		// creates an image from sourceFile
		list($sourceImageIdentifier, $sourceImageWidth, $sourceImageHeight) = $this->create_image_from_source_file();
		// if aspect ratio needs to be maintained
		if ($this->maintainAspectRatio) {
			// calculates image's aspect ratio
			$aspectRatio =
			$sourceImageWidth <= $sourceImageHeight ?
			$sourceImageHeight / $sourceImageWidth :
			$sourceImageWidth / $sourceImageHeight;
			$targetImageWidth = $sourceImageWidth;
			$targetImageHeight = $sourceImageHeight;
			// if width of image is greater than resizeToWidth property and resizeIfGreater property is TRUE
			// or width of image is smaller than resizeToWidth property and resizeIfSmaller property is TRUE
			if (
			($this->resizeToWidth >= 0 && $targetImageWidth > $this->resizeToWidth && $this->resizeIfGreater) ||
			($this->resizeToWidth >= 0 && $targetImageWidth < $this->resizeToWidth && $this->resizeIfSmaller)
			) {
				// set the width of target image
				$targetImageWidth = $this->resizeToWidth;
				// set the height of target image so that the image will keep its aspect ratio
				$targetImageHeight =
				$sourceImageWidth <= $sourceImageHeight ?
				$targetImageWidth * $aspectRatio :
				$targetImageWidth / $aspectRatio;
			}
			// if height of image is greater than resizeToHeight property and resizeIfGreater property is TRUE
			// or height of image is smaller than resizeToHeight property and resizeIfSmaller property is TRUE
			if (
			($this->resizeToHeight >= 0 && $targetImageHeight > $this->resizeToHeight && $this->resizeIfGreater) ||
			($this->resizeToHeight >= 0 && $targetImageHeight < $this->resizeToHeight && $this->resizeIfSmaller)
			) {
				// set the width of target image
				$targetImageHeight = $this->resizeToHeight;
				// set the width of target image so that the image will keep its aspect ratio
				$targetImageWidth =
				$sourceImageWidth <= $sourceImageHeight ?
				$targetImageHeight / $aspectRatio :
				$targetImageHeight * $aspectRatio;
			}
			// if aspect ratio does not need to be maintained
		} else {
			$targetImageWidth = ($this->resizeToWidth >= 0 ? $this->resizeToWidth : $sourceImageWidth);
			$targetImageHeight = ($this->resizeToHeight >= 0 ? $this->resizeToHeight : $sourceImageHeight);
		}
		
		// prepares the target image
		$targetImageIdentifier = $this->create_target_image_identifier($targetImageWidth, $targetImageHeight);
		imagealphablending($targetImageIdentifier, false);
		imagesavealpha($targetImageIdentifier, TRUE);
		
		if(($targetImageWidth != $sourceImageWidth) || ($targetImageHeight != $sourceImageHeight)){
			// resizes image
			imagecopyresampled($targetImageIdentifier, $sourceImageIdentifier, 0, 0, 0, 0, $targetImageWidth, $targetImageHeight, $sourceImageWidth, $sourceImageHeight);
		}
		else {
			imagecopy($targetImageIdentifier, $sourceImageIdentifier, 0, 0, 0, 0, $sourceImageWidth, $sourceImageHeight);
		}
		
		// writes image
		return $this->output_target_image($targetImageIdentifier);
	}
	/**
	 *  Flips horizontally the image given as {@link sourceFile} and outputs the resulted image as {@link targetFile}
	 *
	 *  @return boolean     TRUE on success, FALSE on error.
	 *                      If FALSE is returned, check the {@link error} property to see what went wrong
	 */
	public function flip_horizontal(){
		// creates an image from sourceFile
		list($sourceImageIdentifier, $sourceImageWidth, $sourceImageHeight) = $this->create_image_from_source_file();
		// prepares the target image
		$targetImageIdentifier = $this->create_target_image_identifier($sourceImageWidth, $sourceImageHeight);
		// flips image horizontally
		for ($x = 0; $x < $sourceImageWidth; $x++) {
			imagecopy($targetImageIdentifier, $sourceImageIdentifier, $x, 0, $sourceImageWidth - $x - 1, 0, 1, $sourceImageHeight);
		}
		// writes image
		return $this->output_target_image($targetImageIdentifier);
	}
	/**
	 *  Flips vertically the image given as {@link sourceFile} and outputs the resulted image as {@link targetFile}
	 *
	 *  @return boolean     TRUE on success, FALSE on error.
	 *                      If FALSE is returned, check the {@link error} property to see what went wrong
	 */
	public function flip_vertical(){
		// creates an image from sourceFile
		list($sourceImageIdentifier, $sourceImageWidth, $sourceImageHeight) = $this->create_image_from_source_file();
		// prepares the target image
		$targetImageIdentifier = $this->create_target_image_identifier($sourceImageWidth, $sourceImageHeight);
		// flips image vertically
		for ($y = 0; $y < $sourceImageHeight; $y++) {
			imagecopy($targetImageIdentifier, $sourceImageIdentifier, 0, $y, 0, $sourceImageHeight - $y - 1, $sourceImageWidth, 1);
		}
		// writes image
		return $this->output_target_image($targetImageIdentifier);
	}
	/**
	 *  Rotates the image given as {@link sourceFile} and outputs the resulted image as {@link targetFile}
	 *
	 *  this method implements PHP's imagerotate method which is buggy.
	 *  an improved version of this method should be available soon
	 *
	 *  @param  double  $angle      angle to rotate the image by
	 *  @param  mixed   $bgColor    the color of the uncovered zone after the rotation
	 *
	 *  @return boolean     TRUE on success, FALSE on error.
	 *                      If FALSE is returned, check the {@link error} property to see what went wrong
	 */
	public function rotate($angle, $bgColor){
		// creates an image from sourceFile
		list($sourceImageIdentifier, $sourceImageWidth, $sourceImageHeight) = $this->create_image_from_source_file();
		// rotates image
		$targetImageIdentifier = imagerotate($sourceImageIdentifier, $angle, $bgColor);
		// writes image
		return $this->output_target_image($targetImageIdentifier);
	}

	/**
	 * Creates black and white image from a file
	 * 
	 * 
	 * */
	//Creates yiq function
	private function yiq($r,$g,$b) { return (($r*0.299)+($g*0.587)+($b*0.114)); } 
	
	public function makeBlackAndWhite(){
		// creates an image from sourceFile
		list($sourceImageIdentifier, $sourceImageWidth, $sourceImageHeight) = $this->create_image_from_source_file();
		// Creating the Canvas
		$targetImageIdentifier = imagecreate($sourceImageWidth, $sourceImageHeight);
		//Creates the 256 color palette
		for ($c=0;$c<256;$c++) { $palette[$c] = imagecolorallocate($targetImageIdentifier,$c,$c,$c); }
		
		//Reads the origonal colors pixel by pixel
		for ($y=0;$y<$sourceImageHeight;$y++) {
			for ($x=0;$x<$sourceImageWidth;$x++) {
				$rgb = imagecolorat($sourceImageIdentifier,$x,$y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				//This is where we actually use yiq to modify our rbg values, and then convert them to our grayscale palette
				$gs = $this->yiq($r,$g,$b);
				imagesetpixel($targetImageIdentifier,$x,$y,$palette[$gs]); 
			}
		}
		// writes image
		return $this->output_target_image($targetImageIdentifier);
	}
	
	public function crop($width, $height, $crop_top = 0, $crop_left = 0) {
		
		list($sourceImageIdentifier, $sourceImageWidth, $sourceImageHeight) = $this->create_image_from_source_file();

        if(!$crop_top){
		    if($sourceImageWidth > $width)
			    $crop_top = floor(($sourceImageWidth - $width)/2);
		    else
			    $crop_top = 0;
        }

        if(!$crop_left){
		    if($sourceImageHeight > $height)
			    $crop_left = floor(($sourceImageHeight - $height)/2);
		    else
			    $crop_left = 0;

        }

        $targetImageIdentifier = $this->create_target_image_identifier($width, $height);
        //$crop_top = 0;
		//sprint 0;
		imagecopy($targetImageIdentifier, $sourceImageIdentifier, 0, 0, $crop_top, $crop_left, $width, $height);
		
		// writes image
		return $this->output_target_image($targetImageIdentifier);
		
	}
}
?>