<?php
namespace Arrow\Package\Media;

class MediaWaterMarkOperation extends \Arrow\Object implements IMediaOperation{

	public static function getConfigurationOptions(){
		return array( "image" => ""  );
	}
	public static function doOperation(  MediaElement $element, MediaVersionResult $version = null, $onOrginal , $config ){
		if($onOrginal)
			$resource = $element[ MediaElement::F_PATH ];
		else
			$resource = $version[ MediaVersionResult::F_PATH ];
		
		
		
		$im = new ImageTransform();
		//create_image_from_source_file
		$im->load($resource);
		$im->setTargetFile($resource);
		$im->watermark($config["image"]);
		
	
		
		
	}
}
?>