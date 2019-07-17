<?php
namespace Arrow\Access\Models;

use Arrow\ConfigProvider;
use Arrow\Kernel;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ViewManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;


class AccessAPI
{

    const GROUP_EVERYONE = "Everyone";
    const GROUP_EVERYONE_KEY = 1;
    const GROUP_DEVELOPERS = "Developers";
    const GROUP_DEVELOPERS_KEY = 2;
    const GROUP_ADMINISTRATORS = "Administrators";
    const GROUP_ADMINISTRATORS_KEY = 4;


    public static function checkAccessToPoints(array $points): array
    {
        /** @var Auth $auth */
        $auth = Kernel::getProject()->getContainer()->get(Auth::class);
        $accessSum = $auth->getUser()->getAccessGroupsSum();

        $locatedPoints = AccessPoint::get()
            ->c("point_object_friendly_id", $points, Criteria::C_IN)
            ->find(true, AccessPoint::F_POINT_OBJECT_FRIENDLY_ID);

        $tmp = [];

        foreach ($points as $el) {
            if (isset($locatedPoints[$el])) {
                if ($locatedPoints[$el]->_controlEnabled() == "0") {
                    $tmp[$el] = true;
                } else {
                    $tmp[$el] = self::checkAccessDataHelper($accessSum, (int)$locatedPoints[$el]->_groups());
                }

            } else {
                $point = new AccessPoint([
                    AccessPoint::F_POINT_TYPE => 'route',
                    AccessPoint::F_POINT_ACTION => '',
                    AccessPoint::F_POINT_OBJECT_FRIENDLY_ID => $el,
                    AccessPoint::F_ADDITIONAL_INFO => '',
                    AccessPoint::F_CONTROL_ENABLED => 1
                ]);
                $point->save();
                $tmp[$el] = false;
            }

        }
        return $tmp;
    }


    private static function checkAccessDataHelper(int $authorizedElementGroups, int $accessElementGroups): bool
    {
        if ($accessElementGroups & 1 == 1) {
            return true;
        }

        if (
            !(
                ($authorizedElementGroups & AccessAPI::GROUP_ADMINISTRATORS_KEY) == AccessAPI::GROUP_ADMINISTRATORS_KEY
                ||
                ($authorizedElementGroups & AccessAPI::GROUP_DEVELOPERS_KEY) == AccessAPI::GROUP_DEVELOPERS_KEY
            )
            &&
            (
                !(
                    $accessElementGroups & $authorizedElementGroups
                )
                ||
                $accessElementGroups == 0
            )
        ) {

            return false;
        }

        return true;
    }

    public static function checkAccess($pointType, $pointAction, $pointObjectFriendlyId, $pointObject = null, $additionalInfo = "")
    {


        if (Kernel::isInCLIMode()) {
            return true;
        }

        //@todo wywalic obsluge bazy danych do handlera póki co ten if musi wystarczyc
        if (!class_exists('Arrow\ORM\Persistent\Criteria')) {
            return true;
        }

        $pointObjectFriendlyId = str_replace("\\", "/", $pointObjectFriendlyId);

        $point = AccessPoint::get()
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

            $authService = Auth::getDefault();
            if (!$authService->isLogged()) {
                return false;
            } else {
                $user = $authService->getUser();
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

    public static function accessDenyProcedure($denyInfo = "")
    {

        header("X-Auth-Deny: 1");

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
                exit("Access deny to: `" . $denyInfo . "`");
            } else {
                $login = ConfigProvider::get("redirects")["login"];


                $redirect = $request->getBasePath() . $login;
                if ($request->getPathInfo() != $login) {
                    $redirect .= "?" . http_build_query(["from" => $request->getPathInfo() . "?" . $request->getRequestUri()]);
                }

                $response = new RedirectResponse($redirect);
                $response->prepare($request);


                $response->send();

                exit();

            }

            exit();

        } else {
            //$logoutLink = \Arrow\Router::link('access/auth/logout');
            exit("Access deny to: `" . $denyInfo . "`");
        }
    }


}

?>
