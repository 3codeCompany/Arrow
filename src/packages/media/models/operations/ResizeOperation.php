<?php
namespace Arrow\Media;

class MediaResizeOperation extends \Arrow\Object implements IMediaOperation{

	public static function getConfigurationOptions(){
		return array( "targetX" => 200, "targetY" => 200,  "Dopasowanie" => array(0 => "Do obu wymiarów", 1 => "Do jednego z wymiarów")  );
	}
	public static function doOperation(  MediaElement $element, MediaVersionResult $version = null, $onOrginal , $config ){
		if($onOrginal)
			$resource = $element[ MediaElement::F_PATH ];
		else
			$resource = $version[ MediaVersionResult::F_PATH ];
		
		$imTransform = new ImageTransform();
		$imTransform->load( $resource );
		$size = getimagesize($resource);
		$w = $size[0];
		$h = $size[1];
		
		if(($config['Dopasowanie'] == 1)) {
			$rw = $w/$config["targetX"];
			$rh = $h/$config["targetY"];
			
			if($rw > $rh)
				$imTransform->resizeToHeight = $config["targetY"];
			else 
				$imTransform->resizeToWidth = $config["targetX"];
		}
		else {
			$imTransform->resizeToWidth = $config["targetX"];
			$imTransform->resizeToHeight = $config["targetY"];
		}
		
		$imTransform->setTargetFile( $resource );
		$imTransform->resize();
	}
}
?>