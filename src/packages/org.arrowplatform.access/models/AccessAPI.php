<?php
namespace Arrow\Access;

use Arrow\ORM\Persistent\Criteria;
use Arrow\RequestContext;
use Arrow\Router;
use Arrow\ViewManager, Arrow\Controller, Arrow\Models\Action;
use Arrow\Models\Project;

interface AccessControledObject
{
    public function getLoginTemplate();
}

class AccessAPI
{

    const GROUP_EVERYONE = "Everyone";
    const GROUP_EVERYONE_KEY = 1;
    const GROUP_DEVELOPERS = "Developers";
    const GROUP_DEVELOPERS_KEY = 2;
    const GROUP_ADMINISTRATORS = "Administrators";
    const GROUP_ADMINISTRATORS_KEY = 4;

    const USER_INITIAL_DEVELOPER_LOGIN = "dev";
    const USER_INITIAL_DEVELOPER_PASSWORD = "321dev";


    private static $accessPointStandardProperties
        = array(
            AccessPoint::F_POINT_TYPE,
            AccessPoint::F_POINT_ACTION,
            AccessPoint::F_POINT_OBJECT_FRIENDLY_ID,
            AccessPoint::F_ADDITIONAL_INFO,
            AccessPoint::F_CONTROL_ENABLED
        );


    public static function checkAccessToView(Action $view)
    {
        return AccessAPI::checkAccess("view", $view->getPackage(), "show", $view->getPath(), "");
    }

    public static function checkAccess($pointType, $pointAction, $pointObjectFriendlyId, $pointObject = null, $additionalInfo = "")
    {

        //@todo wywalic obsluge bazy danych do handlera póki co ten if musi wystarczyc
        if(!class_exists('Arrow\ORM\Persistent\Criteria' ))
            return true;


        $point = Criteria::query('Arrow\Access\AccessPoint')
            ->c("point_type", $pointType)
            ->c("point_action", $pointAction)
            ->c("point_object_friendly_id", $pointObjectFriendlyId)
            ->findFirst();
        if (!$point) {
            $point = new AccessPoint(array(
                                          AccessPoint::F_POINT_TYPE               => $pointType,
                                          AccessPoint::F_POINT_ACTION             => $pointAction,
                                          AccessPoint::F_POINT_OBJECT_FRIENDLY_ID => $pointObjectFriendlyId,
                                          AccessPoint::F_ADDITIONAL_INFO          => $additionalInfo,
                                          AccessPoint::F_CONTROL_ENABLED          => 1
                                     ));
            $point->save();
        }

        if ($point["control_enabled"]) {

            if (!Auth::getDefault()->isLogged()) {
                return false;
            } else {
                $user = Auth::getDefault()->getUser();
                $accessSum = $user->getAccessGroupsSum();
                $pointGroups = (int)$point[AccessPoint::F_GROUPS];

                if(  $pointGroups & 1 == 1  )
                    return true;

                if (
                    !(
                        ($accessSum & AccessAPI::GROUP_ADMINISTRATORS_KEY) == AccessAPI::GROUP_ADMINISTRATORS_KEY
                        ||
                        ($accessSum & AccessAPI::GROUP_DEVELOPERS_KEY) == AccessAPI::GROUP_DEVELOPERS_KEY
                    )
                    &&
                    (
                        !(
                            $pointGroups & $accessSum
                        )
                        ||
                        $pointGroups == 0
                    )
                ){

                    return false;
                }
            }
        }
        return true;
    }

    public static function accessPoint($pointType, $pointAction, $pointObjectFriendlyId, $pointObject = null, $additionalInfo = "")
    {
        $access = self::checkAccess($pointType, $pointAction, $pointObjectFriendlyId, $pointObject, $additionalInfo);
        //FB::log($pointType.' '.$pointAction.' '.$pointObjectFriendlyId.' '.$pointObject.' '.$additionalInfo);
    }

    public static function accessDenyProcedure($denyInfo = "")
    {
        if (!Auth::getDefault()->isLogged()) {
            

            $_SESSION["arrow"]["access"]["requestedUrl"] = $_SERVER["REQUEST_URI"] . $_SERVER["QUERY_STRING"];

            $login = Project::getInstance()->getSetting("application.view.login");

            if( RequestContext::getDefault()->isXHR() )
                exit("Access deny - please login: ".$denyInfo);
            else{
                Controller::redirectToTemplate($login, [ "from" => $_SESSION["arrow"]["access"]["requestedUrl"] ] );
            }

            exit();

        } else {
            $logoutLink = \Arrow\Router::link('access/auth/logout');
            exit("Access deny <a href=\"$logoutLink\">logout</a> ".$denyInfo);
        }
    }

    public function getAccessMatrix()
    {

    }

    public static function checkInstallation()
    {
        $groups = array(
            self::GROUP_EVERYONE_KEY       => self::GROUP_EVERYONE,
            self::GROUP_DEVELOPERS_KEY     => self::GROUP_DEVELOPERS,
            self::GROUP_ADMINISTRATORS_KEY => self::GROUP_ADMINISTRATORS,
        );
        foreach ($groups as $key => $name) {
            $test = Criteria::query(AccessGroup::getClass())
                ->c(AccessGroup::F_ID, $key)
                ->c(AccessGroup::F_NAME, $name)
                ->count();
            if ($test == 0) {
                throw new \Arrow\Exception(new \Arrow\Models\ExceptionContent(
                    "Access group '$name' not exists"
                ));
            }
        }

        $users = Criteria::query(User::getClass())->count();
        if ($users == 0) {
            throw new \Arrow\Exception(new \Arrow\Models\ExceptionContent(
                "Empty users list"
            ));
        }

    }

    public static function setup()
    {

        try {
            $groups = array(
                self::GROUP_EVERYONE_KEY       => self::GROUP_EVERYONE,
                self::GROUP_DEVELOPERS_KEY     => self::GROUP_DEVELOPERS,
                self::GROUP_ADMINISTRATORS_KEY => self::GROUP_ADMINISTRATORS,
            );


            foreach ($groups as $key => $name) {
                $test = Criteria::query(AccessGroup::getClass())
                    ->c(AccessGroup::F_ID, $key)
                    ->c(AccessGroup::F_NAME, $name)
                    ->count();

                if ($test == 0) {
                    $group = new AccessGroup(array(
                                                  AccessGroup::F_ID   => $key,
                                                  AccessGroup::F_NAME => $name
                                             ));
                    \Arrow\ORM\PersistentFactory::save($group, true, true);
                }
            }


            $users = Criteria::query(User::getClass())->count();
            if ($users == 0) {
                $user = new User(array(
                                      User::F_LOGIN    => self::USER_INITIAL_DEVELOPER_LOGIN,
                                      User::F_PASSWORD => self::USER_INITIAL_DEVELOPER_PASSWORD,
                                      User::F_ACTIVE   => 1
                                 ));
                $user->save();
                $user->setGroups(array(self::GROUP_DEVELOPERS_KEY));
            }

            $pointData = array(
                AccessPoint::F_POINT_TYPE               => "view",
                AccessPoint::F_POINT_ACTION             => "show",
                AccessPoint::F_POINT_OBJECT_FRIENDLY_ID => "/auth/login",
                AccessPoint::F_CONTROL_ENABLED          => 0
            );

            AccessPoint::createIfNotExists($pointData);


            //todo !obowiązkowo przenieść w bardziej siutable miejsce
            $pointData = array(
                AccessPoint::F_POINT_NAMESPACE          => "application",
                AccessPoint::F_POINT_TYPE               => "view",
                AccessPoint::F_POINT_ACTION             => "show",
                AccessPoint::F_POINT_OBJECT_FRIENDLY_ID => "/index",
                AccessPoint::F_CONTROL_ENABLED          => 0
            );

            AccessPoint::createIfNotExists($pointData);


        } catch (\Arrow\Exception $ex) {
            throw new \Arrow\Exception(new \Arrow\Models\ExceptionContent(
                "Can't setup `access` package"
            ), 0, $ex);
        }

    }

    /**
     * Imports access matrix from configuration files to database
     *
     * @param mixed $filter String with package namespace or array with namespaces
     */
    public static function importAccessMatrixFromPackages($filter = false)
    {
        if (is_string($filter)) {
            $filter = array($filter);
        }

        $project = Project::getInstance();

        $groups = Criteria::query(AccessGroup::getClass())
            ->findAsFieldArray(AccessGroup::F_NAME, true);

        $matrixFile = "/conf/access-martrix.xml";

        foreach ($project->getPackages() as $package) {
            if ($filter != false && !in_array($package["namespace"], $filter)) {
                continue;
            }

            if (file_exists($package["dir"] . $matrixFile)) {
                $xml = simplexml_load_file($package["dir"] . $matrixFile);
                foreach ($xml->point as $pointXML) {
                    $criteria = Criteria::query(AccessPoint::getClass());
                    foreach (self::$accessPointStandardProperties as $property) {
                        if ($project != AccessPoint::F_CONTROL_ENABLED) {
                            $criteria->c($property, (string)$pointXML[$property]);
                        }
                    }

                    $point = $criteria->findFirst();
                    if (empty($point)) {
                        $data = array();
                        foreach (self::$accessPointStandardProperties as $property) {
                            $data[$property] = (string)$pointXML[$property];
                        }
                        $point = new AccessPoint($data);
                    }

                    $point[AccessPoint::F_CONTROL_ENABLED] = (string)$pointXML[AccessPoint::F_CONTROL_ENABLED];
                    $accessSum = 0;
                    foreach ($pointXML->group as $groupXML) {
                        $key = (string)$groupXML["id"];
                        $name = (string)$groupXML["name"];
                        if (isset($groups[$key]) && $groups[$key] == $name) {
                            $accessSum += $key;
                        } else {
                            throw new \Arrow\Exception(new \Arrow\Models\ExceptionContent(
                                "The group list is not compatible with importing matrix",
                                "[Importing] Package: {$package["name"]}, key: $key, name: $name"
                            ));
                        }
                    }
                    $point[AccessPoint::F_GROUPS] = $accessSum;
                    $point->save();
                }
            }
        }
    }

    /**
     * Saves access matrix from database to configurations files
     * location of file is [package dir]/conf/access-matrix.xml
     *
     * @param mixed $filter String with package namespace or array with namespaces
     *
     * @throws \Arrow\Exception
     */
    public static function saveAccessMatrixToPackages($filter = false)
    {
        return true;
        if (is_string($filter)) {
            $filter = array($filter);
        }

        $project = Project::getInstance();

        $groups = Criteria::query(AccessGroup::getClass())
            ->findAsFieldArray(AccessGroup::F_NAME, true);

        foreach ($project->getPackages() as $package) {
            if ($filter != false && !in_array($package["namespace"], $filter)) {
                continue;
            }

            $points = Criteria::query(AccessPoint::getClass())
                ->c(AccessPoint::F_POINT_NAMESPACE, $package["namespace"])
                ->find();


            $accessXML = new \SimpleXMLElement("<accessmatrix></accessmatrix>");

            foreach ($points as $point) {
                $pointXML = $accessXML->addChild('point');
                foreach (self::$accessPointStandardProperties as $property) {
                    $pointXML->addAttribute($property, $point->getValue($property));
                }

                foreach ($groups as $id => $name) {
                    if ($id & $point["groups"]) {
                        /**
                         * todo zbadać czy nie da się jakoś tego ominąć ( zapis grup innych niż podstawowowe dla systemu )
                         * może zapis innych grup podstawowowych ( pliki setup.xml )
                         */
                        if ($id > 4 && $package["namespace"] != "application") {
                            throw new \Arrow\Exception(new \Arrow\Models\ExceptionContent(
                                "Can't save non basic group into access",
                                "Point: " . $point[AccessPoint::F_POINT_OBJECT_FRIENDLY_ID],
                                "Group name: " . $name
                            ));
                        }

                        $group = $pointXML->addChild('group');
                        $group->addAttribute(AccessGroup::F_ID, $id);
                        $group->addAttribute(AccessGroup::F_NAME, $name);
                    }
                }
            }

            /**
             * Saving doucment
             */
            $dom = new \DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($accessXML->asXml());
            file_put_contents($package["dir"] . "/conf/access-martrix.xml", $dom->saveXML());


        }
    }


}

?>