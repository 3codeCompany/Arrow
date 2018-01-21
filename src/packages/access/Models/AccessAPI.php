<?php
namespace Arrow\Access\Models;

use Arrow\ConfigProvider;
use Arrow\Controller;
use Arrow\Models\Action;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ViewManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use function htmlentities;
use function var_dump;


class AccessAPI
{

    const GROUP_EVERYONE = "Everyone";
    const GROUP_EVERYONE_KEY = 1;
    const GROUP_DEVELOPERS = "Developers";
    const GROUP_DEVELOPERS_KEY = 2;
    const GROUP_ADMINISTRATORS = "Administrators";
    const GROUP_ADMINISTRATORS_KEY = 4;


    public static function checkAccessToView(Action $view)
    {
        return AccessAPI::checkAccess("view", $view->getPackage(), "show", $view->getPath(), "");
    }

    public static function checkAccess($pointType, $pointAction, $pointObjectFriendlyId, $pointObject = null, $additionalInfo = "")
    {


        if (Controller::isInCLIMode()) {
            return true;
        }

        //@todo wywalic obsluge bazy danych do handlera póki co ten if musi wystarczyc
        if (!class_exists('Arrow\ORM\Persistent\Criteria')) {
            return true;
        }

        $pointObjectFriendlyId = str_replace("\\", "/", $pointObjectFriendlyId);

        $point = Criteria::query('Arrow\Access\Models\AccessPoint')
            ->c("point_type", $pointType)
            ->c("point_action", $pointAction)
            ->c("point_object_friendly_id", $pointObjectFriendlyId)
            ->findFirst();
        if (!$point) {
            $point = new AccessPoint([
                AccessPoint::F_POINT_TYPE => $pointType,
                AccessPoint::F_POINT_ACTION => $pointAction,
                AccessPoint::F_POINT_OBJECT_FRIENDLY_ID => $pointObjectFriendlyId,
                AccessPoint::F_ADDITIONAL_INFO => $additionalInfo,
                AccessPoint::F_CONTROL_ENABLED => 1
            ]);
            $point->save();
        }

        if ($point["control_enabled"]) {

            if (!Auth::getDefault()->isLogged()) {
                return false;
            } else {
                $user = Auth::getDefault()->getUser();
                $accessSum = $user->getAccessGroupsSum();
                $pointGroups = (int)$point[AccessPoint::F_GROUPS];

                if ($pointGroups & 1 == 1) {
                    return true;
                }

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
                ) {

                    return false;
                }
            }
        }
        return true;
    }

    public static function accessPoint($pointType, $pointAction, $pointObjectFriendlyId, $pointObject = null, $additionalInfo = "")
    {
        $access = self::checkAccess($pointType, $pointAction, $pointObjectFriendlyId, $pointObject, $additionalInfo);
    }

    public static function accessDenyProcedure($denyInfo = "")
    {

        if (!Auth::getDefault()->isLogged()) {


            if (isset($_SERVER["REQUEST_URI"])) {
                $_SESSION["arrow"]["access"]["requestedUrl"] = $_SERVER["REQUEST_URI"] . $_SERVER["QUERY_STRING"];
            }


            $request = Request::createFromGlobals();
            //if xhr or post query
            if ($request->isXmlHttpRequest()) {
                (new JsonResponse(["accessDeny" => $denyInfo]))->send();
                exit();
            } elseif ($request->request->count() != 0) {
                exit("Access deny - please login" . $denyInfo);
            } else {
                $login = ConfigProvider::get("redirects")["login"];





                $redirect = $request->getBasePath().$login;
                if($request->getPathInfo() != $login){
                    $redirect .=  "?" . http_build_query(["from" => $request->getPathInfo() . "?" . $request->getRequestUri()]);
                }

                $response = new RedirectResponse( $redirect );
                $response->prepare($request);


                $response->send();

                exit();

            }

            exit();

        } else {
            $logoutLink = \Arrow\Router::link('access/auth/logout');
            exit("Access deny <a href=\"$logoutLink\">logout</a> " . $denyInfo);
        }
    }

    public function getAccessMatrix()
    {

    }

    public static function checkInstallation()
    {
        $groups = array(
            self::GROUP_EVERYONE_KEY => self::GROUP_EVERYONE,
            self::GROUP_DEVELOPERS_KEY => self::GROUP_DEVELOPERS,
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
                self::GROUP_EVERYONE_KEY => self::GROUP_EVERYONE,
                self::GROUP_DEVELOPERS_KEY => self::GROUP_DEVELOPERS,
                self::GROUP_ADMINISTRATORS_KEY => self::GROUP_ADMINISTRATORS,
            );


            foreach ($groups as $key => $name) {
                $test = Criteria::query(AccessGroup::getClass())
                    ->c(AccessGroup::F_ID, $key)
                    ->c(AccessGroup::F_NAME, $name)
                    ->count();

                if ($test == 0) {
                    $group = new AccessGroup(array(
                        AccessGroup::F_ID => $key,
                        AccessGroup::F_NAME => $name
                    ));
                    \Arrow\ORM\PersistentFactory::save($group, true, true);
                }
            }


            $users = Criteria::query(User::getClass())->count();
            if ($users == 0) {
                $user = new User(array(
                    User::F_LOGIN => self::USER_INITIAL_DEVELOPER_LOGIN,
                    User::F_PASSWORD => self::USER_INITIAL_DEVELOPER_PASSWORD,
                    User::F_ACTIVE => 1
                ));
                $user->save();
                $user->setGroups(array(self::GROUP_DEVELOPERS_KEY));
            }

            $pointData = array(
                AccessPoint::F_POINT_TYPE => "view",
                AccessPoint::F_POINT_ACTION => "show",
                AccessPoint::F_POINT_OBJECT_FRIENDLY_ID => "/auth/login",
                AccessPoint::F_CONTROL_ENABLED => 0
            );

            AccessPoint::createIfNotExists($pointData);


            //todo !obowiązkowo przenieść w bardziej siutable miejsce
            $pointData = array(
                AccessPoint::F_POINT_NAMESPACE => "application",
                AccessPoint::F_POINT_TYPE => "view",
                AccessPoint::F_POINT_ACTION => "show",
                AccessPoint::F_POINT_OBJECT_FRIENDLY_ID => "/index",
                AccessPoint::F_CONTROL_ENABLED => 0
            );

            AccessPoint::createIfNotExists($pointData);


        } catch (\Arrow\Exception $ex) {
            throw new \Arrow\Exception(new \Arrow\Models\ExceptionContent(
                "Can't setup `access` package"
            ), 0, $ex);
        }

    }


}

?>
