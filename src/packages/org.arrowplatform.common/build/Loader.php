<?php
namespace Arrow\Package\Common;
use Arrow\Package\Access\AccessPoint;

class Loader1
{

    public final static function registerAutoload()
    {
        {
            $classes = array(
                'arrow\\package\\common\\administrationextensionsmanager' => '/panel/AdministrationExtensionsManager.php',
                'arrow\\package\\common\\breadcrumbgenerator' => '/panel/BreadcrumbGenerator.php',
                'arrow\\package\\common\\administrationextensionpoint' => '/panel/AdministrationExtensionPoint.php',
                'arrow\\package\\common\\ormobjectstracker' => '/track/ORMObjectsTracker.php',
                'arrow\\package\\common\\ormobjectsarchiver' => '/track/ORMObjectsArchiver.php',
                'arrow\\package\\common\\track' => '/track/Track.php',
                'arrow\\package\\common\\archive' => '/track/Archive.php',
                'arrow\\package\\common\\controller' => '/../controllers/Controller.php',
                'arrow\\package\\common\\datacontroller' => '/../controllers/DataController.php',
                'arrow\\package\\common\\administrationlayout' => '/../layouts/AdministrationLayout.php',
                'arrow\\package\\common\\administrationpopuplayout' => '/../layouts/AdministrationPopupLayout.php',
                'arrow\\package\\common\\emptylayout' => '/../layouts/EmptyLayout.php',
                'arrow\\package\\common\\popupformbuilder' => '/panel/PopupFormBuilder.php',
                'arrow\\package\\common\\imultilangobject' => '/languages/IMultilangObject.php',
                'arrow\\package\\common\\translations' => '/languages/Translations.php',
                'arrow\\package\\common\\multifile' => '/form/MultiFile.php',
                'arrow\\package\\common\\tabledatasource' => '/table/TableDataSource.php',
            );
            \Arrow\Models\Loader::registerClasses(__DIR__."/models", $classes);

        }


    }

    public static function install(){


    }

}