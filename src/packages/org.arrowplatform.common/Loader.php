<?php
namespace Arrow\Package\Common;
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 14.08.12
 * Time: 10:41
 * To change this template use File | Settings | File Templates.
 */
class Loader
{

    public final static function registerAutoload()
    {
        $classes = array(
            'arrow\\package\\common\\administrationextensionpoint' => '/models/panel/AdministrationExtensionPoint.php',
            'arrow\\package\\common\\administrationextensionsmanager' => '/models/panel/AdministrationExtensionsManager.php',
            'arrow\\package\\common\\administrationlayout' => '/layouts/AdministrationLayout.php',
            'arrow\\package\\common\\administrationpopuplayout' => '/layouts/AdministrationPopupLayout.php',
            'arrow\\package\\common\\archive' => '/models/track/Archive.php',
            'arrow\\package\\common\\breadcrumbgenerator' => '/models/panel/BreadcrumbGenerator.php',
            'arrow\\package\\common\\controller' => '/controllers/Controller.php',
            'arrow\\package\\common\\datacontroller' => '/controllers/DataController.php',
            'arrow\\package\\common\\emptylayout' => '/layouts/EmptyLayout.php',
            'arrow\\package\\common\\imultilangobject' => '/models/languages/IMultilangObject.php',
            'arrow\\package\\common\\links' => '/models/Wigets/Links.php',
            'arrow\\package\\common\\formhelper' => '/models/Wigets/FormHelper.php',
            'arrow\\package\\common\\tablehelper' => '/models/Wigets/TableHelper.php',
            'arrow\\package\\common\\multifile' => '/models/Wigets/form/MultiFile.php',
            'arrow\\package\\common\\tabledatasource' => '/models/Wigets/table/TableDataSource.php',
            'arrow\\package\\common\\arraydatasource' => '/models/Wigets/table/ArrayDataSource.php',
            'arrow\\package\\common\\ormobjectsarchiver' => '/models/track/ORMObjectsArchiver.php',
            'arrow\\package\\common\\ormobjectstracker' => '/models/track/ORMObjectsTracker.php',
            'arrow\\package\\common\\popupformbuilder' => '/models/panel/PopupFormBuilder.php',
            'arrow\\package\\common\\stdclass' => '/controllers/DataController.php',
            'arrow\\package\\common\\track' => '/models/track/Track.php',
            'arrow\\package\\common\\translations' => '/models/languages/Translations.php',
            'arrow\\package\\common\\history' => '/models/history/History.php',
            'arrow\\package\\common\\historytable' => '/models/history/HistoryTable.php'
        );


        \Arrow\Models\Loader::registerClasses(__DIR__, $classes);


    }


}
