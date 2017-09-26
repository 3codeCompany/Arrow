<?php
namespace Arrow\Media;

class MediaToGrayScaleOperation extends \Arrow\Object implements IMediaOperation{
	
	public static function getConfigurationOptions(){
		return array( 'nic' => '' );
	}
	public static function doOperation(  MediaElement $element, MediaVersionResult $version = null, $onOrginal , $config ){
		if($onOrginal)
			$resource = $element[ MediaElement::F_PATH ];
		else
			$resource = $version[ MediaVersionResult::F_PATH ];
			
		$imTransform = new ImageTransform();
		$imTransform->load( $resource );
		$imTransform->setTargetFile( $resource );
		$imTransform->makeBlackAndWhite( $resource );
	}
}
?>