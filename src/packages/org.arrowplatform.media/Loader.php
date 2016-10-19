<?php
namespace Arrow\Package\Media;

class Loader
{

    public final static function registerAutoload()
    {
        {
            $classes = array(
                'arrow\\package\\media\\administrationextensionpoint' => '/panel/AdministrationExtensionPoint.php',
                'arrow\\package\\media\\element' => '/Element.php',
                'arrow\\package\\media\\elementconnection' => '/ElementConnection.php',
                'arrow\\package\\media\\folder' => '/Folder.php',
                'arrow\\package\\media\\imediaoperation' => '/IMediaOperation.php',
                'arrow\\package\\media\\mediaapi' => '/MediaAPI.php',
                'arrow\\package\\media\\mediacropoperation' => '/operations/CropOperation.php',
                'arrow\\package\\media\\mediaresizeoperation' => '/operations/ResizeOperation.php',
                'arrow\\package\\media\\mediatograyscaleoperation' => '/operations/ToGrayScaleOperation.php',
                'arrow\\package\\media\\mediawatermarkoperation' => '/operations/WaterMarkOperation.php',
                'arrow\\package\\media\\controller' => '/../controllers/Controller.php',
            );
            \Arrow\Models\Loader::registerClasses(__DIR__."/models", $classes);

        }


    }

}