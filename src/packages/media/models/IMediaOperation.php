<?php
namespace Arrow\Media;
interface IMediaOperation{
	public static function getConfigurationOptions();
	public static function doOperation( MediaElement $element, MediaVersionResult $version =null, $onOrginal, $config  );
}
?>