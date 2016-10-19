<?php
namespace Arrow\Models;
require (__DIR__."/Loader.php");
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 14.08.12
 * Time: 10:41
 * To change this template use File | Settings | File Templates.
 */
class LoaderSetup{

    public final static function registerAutoload(){
        $classes = array(
            'arrow\\models\\abstractlayout' => '/engine/templatesSystem/AbstractLayout.php',
                'arrow\\models\\accessexception' => '/engine/access/AccessException.php',
                'arrow\\models\\action' => '/engine/router/Action.php',
                'arrow\\models\\applicationexception' => '/exception/ApplicationException.php',
                'arrow\\models\\authhandler' => '/standardHandlers/AuthHandler.php',
                'arrow\\models\\consolecommands' => '/engine/server/ConsoleCommands.php',
                'arrow\\models\\controller' => '/engine/controller/Controller.php',
                'arrow\\models\\db' => '/DB/DB.php',
                'arrow\\models\\dispatcher' => '/engine/router/Dispatcher.php',
                'arrow\\models\\errorhandler' => '/standardHandlers/ErrorHandler.php',
                'arrow\\models\\exceptioncontent' => '/exception/ExceptionContent.php',
                'arrow\\models\\exceptionhandler' => '/exception/ExceptionHandler.php',
                'arrow\\models\\iaccessmanager' => '/interfaces/IAccessManager.php',
                'arrow\\models\\iaction' => '/engine/router/IAction.php',
                'arrow\\models\\iauthhandler' => '/engine/IProjectHandlers/IAuthHandler.php',
                'arrow\\models\\icontrolable' => '/model/IControlable.php',
                'arrow\\models\\icontroller' => '/engine/controller/IController.php',
                'arrow\\models\\icontrolobject' => '/engine/access/IControlObject.php',
                'arrow\\models\\idatasource' => '/model/IDataSource.php',
                'arrow\\models\\idatasourcefactory' => '/model/IDataSourceFactory.php',
                'arrow\\models\\ierrorhandler' => '/engine/IProjectHandlers/IErrorHandler.php',
                'arrow\\models\\iexceptionhandler' => '/engine/IProjectHandlers/IExceptionHandler.php',
                'arrow\\models\\iloader' => '/engine/IProjectHandlers/ILoader.php',
                'arrow\\models\\iobjectserialize' => '/remote/IObjectSerialize.php',
                'arrow\\models\\iparsersprovider' => '/engine/router/IParsersProvider.php',
                'arrow\\models\\iremoteresponsehandler' => '/engine/IProjectHandlers/IRemoteResponseHandler.php',
                'arrow\\models\\isessionhandler' => '/engine/IProjectHandlers/ISessionHandler.php',
                'arrow\\models\\isingleton' => '/interfaces/ISingleton.php',
                'arrow\\models\\istandardcontroller' => '/interfaces/IStandardController.php',
                'arrow\\models\\iuniqueobject' => '/interfaces/IUniqueObject.php',
                'arrow\\models\\iuser' => '/engine/access/IUser.php',
                'arrow\\models\\loader' => '/loader/Loader.php',
                'arrow\\models\\loadersetup' => '/loader/LoaderSetup.php',
                'arrow\\models\\logger\\consolestream' => '/logger/Logger.php',
                'arrow\\models\\logger\\filestream' => '/logger/Logger.php',
                'arrow\\models\\logger\\logger' => '/logger/Logger.php',
                'arrow\\models\\logger\\stdoutputstream' => '/logger/Logger.php',
                'arrow\\models\\logger\\stream' => '/logger/Logger.php',
                'arrow\\models\\project' => '/engine/projects/Project.php',
                'arrow\\models\\projectgenerator' => '/engine/projects/ProjectGenerator.php',
                'arrow\\models\\remoteresponsehandler' => '/standardHandlers/RemoteResponseHandler.php',
                'arrow\\models\\resource' => '/engine/resources/Resource.php',
                'arrow\\models\\resources' => '/engine/resources/Resources.php',
                'arrow\\models\\serializableclosure' => '/closure/SerializableClosure.php',
                'arrow\\models\\sessionhandler' => '/standardHandlers/SessionHandler.php',
                'arrow\\models\\setting' => '/engine/projects/Setting.php',
                'arrow\\models\\settings' => '/engine/projects/Settings.php',
                'arrow\\models\\standardparsersprovider' => '/engine/router/StandardParsersProvider.php',
                'arrow\\models\\viewparser' => '/engine/router/ViewParser.php'
        );

        Loader::registerClasses( ARROW_ROOT_PATH."/src/server/models/", $classes);
        Loader::registerAutoload();

    }



}
