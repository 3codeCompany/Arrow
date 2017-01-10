<?php
namespace Arrow\Access;

class Loader
{

    public final static function registerAutoload()
    {
        {
            $classes = array(
                'arrow\\package\\access\\accessapi' => '/AccessAPI.php',
                'arrow\\package\\access\\accesscontroledobject' => '/AccessAPI.php',
                'arrow\\package\\access\\accessgroup' => '/AccessGroup.php',
                'arrow\\package\\access\\accessmanager' => '/AccessManager.php',
                'arrow\\package\\access\\accesspoint' => '/AccessPoint.php',
                'arrow\\package\\access\\accesspointgroup' => '/AccessPointGroup.php',
                'arrow\\package\\access\\accessusergroup' => '/AccessUserGroup.php',
                'arrow\\package\\access\\administrationextensionpoint' => '/panel/AdministrationExtensionPoint.php',
                'arrow\\package\\access\\auth' => '/Auth.php',
                'arrow\\package\\access\\user' => '/User.php',
                'arrow\\package\\access\\userformvalidator' => '/UserFormValidator.php',
                'arrow\\package\\access\\accesscontroller' => '/../controllers/AccessController.php',
            );
            \Arrow\Models\Loader::registerClasses(__DIR__."/models", $classes);

        }

    }

    public static function install(){
        try {
            AccessAPI::checkInstallation();
        } catch (\Arrow\Exception $ex) {
            AccessAPI::setup();
            exit("Access API setup finish");
        }
    }

}