<?php
namespace Arrow\Access\Models;
use Arrow\Common\Models\Track\Track;
use Arrow\Models\SessionHandler;
use Arrow\ORM\DB\DB;
use Arrow\ORM\Persistent\Criteria;
use Arrow\RequestContext;
use Arrow\Router;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth
{
    /**
     * Object instance keeper
     *
     * @var Auth
     */
    private static $oInstance = null;

    /**
     * User object
     *
     * @var User
     */
    private $user = null;

    /**
     * Container for account if loginAs used
     * @var User
     */
    private $shadowUser = null;

    private $loginErrorMessage = "";

    private static $rememberCookieName = "arrow_access_remember_key";

    private static $maxBadLog = 5;

    /**
     * Singleton IAuthHandlerImplementation
     *
     * @return Auth
     */
    public static function getDefault()
    {
        if (self::$oInstance == null) {
            self::$oInstance = new Auth();
        }
        return self::$oInstance;
    }

    private function __construct()
    {
        $id = SessionHandler::getDefault()->getUserId();

        if ($id) {
            $this->user = User::get()->findByKey($id);
        } elseif (function_exists("getallheaders")) {
            $headers = getallheaders();

            if (isset($headers["authorization"])) {
                try {
                    $secretKey = $_ENV["JWT_SECRET"];
                    $x = ["HS512"];
                    //$decoded = JWT::decode(str_replace("Token ", "", $headers["authorization"]), $secretKey, $x);

                    $headers  = new \stdClass();
                    $decoded = JWT::decode(str_replace("Token ", "", $headers["authorization"]), new Key($secretKey, 'HS256'), $headers );
                    $this->user = User::get()->findByKey($decoded->data->userId);
                } catch (ExpiredException $ex) {
                    AccessAPI::accessDenyProcedure("Token expired");
                    exit();
                }
            }
        }

        if (isset($_SESSION["auth"]["shadowUser"]) && is_string($_SESSION["auth"]["shadowUser"])) {
            $this->shadowUser = unserialize($_SESSION["auth"]["shadowUser"]);
        }

        if (is_null($this->user)) {
            $this->loginByRememberCookie();
        }
    }

    /**
     * @return int
     */
    public static function getMaxBadLog(): int
    {
        return self::$maxBadLog;
    }

    /**
     * @param int $maxBadLog
     */
    public static function setMaxBadLog(int $maxBadLog): void
    {
        self::$maxBadLog = $maxBadLog;
    }

    /**
     * Getter for user object
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return \Arrow\Access\Models\User
     */
    public function getShadowUser()
    {
        return $this->shadowUser;
    }

    public function setRememberCookie()
    {
        if ($this->isLogged()) {
            $this->user["remember_key"] = md5(microtime());
            $this->user->save();
            $key = substr($this->user["login"], 0, 2) . "_" . $this->user["remember_key"];
            setcookie(self::$rememberCookieName, $key, time() + 3600 * 24 * 7, Router::getBasePath());
        } else {
            throw new Exception("User not logged");
        }
    }

    private function loginByRememberCookie()
    {
        if (!isset($_COOKIE[self::$rememberCookieName])) {
            return false;
        }

        $str = $_COOKIE[self::$rememberCookieName];
        $hash = substr($str, 3);
        $loginPart = substr($str, 0, 2);

        $user = Criteria::query(User::getClass())
            ->c("remember_key", $hash)
            ->findFirst();

        if (!$user || substr($user["login"], 0, 2) != $loginPart) {
            return false;
        } else {
            $this->user = $user;
            SessionHandler::getDefault()->assignUser($user["id"]);
            $this->setRememberCookie();
            return true;
        }
    }

    private function destroyRememberCookie()
    {
        //unset($_COOKIE[self::$rememberCookieName]);
        //return setcookie(self::$rememberCookieName, NULL, -1, Router::getDefault()->getBasePath());
    }

    public function restoreShadowUser()
    {
        $this->user = $this->shadowUser;

        $_SESSION["auth"]["user"] = serialize($this->user);
        SessionHandler::getDefault()->assignUser($this->user["id"]);
        $this->shadowUser = null;
        unset($_SESSION["auth"]["shadowUser"]);
    }

    /**
     * Login
     *
     * @param String $login
     * @param String $password
     *
     * @param bool $loginAs
     * @param bool $user
     * @return boolean
     * @throws Exception
     * @throws \Arrow\ORM\Exception
     */

    public function doLogin($login, $password, $loginAs = false, $user = false)
    {
        $this->loginErrorMessage = "";

        if (!$user) {
            $user = User::get()
                ->_login($login)
                ->find();

            $result = $user->toArray();
            if (count($result) > 1) {
                throw new \Arrow\Exception(["msg" => "Istnieją identyczne loginy: " . $login]);
            }

            if (count($result) == 0) {
                $this->loginErrorMessage = "Podany login lub hasło nie zgadzają się.";
                return false;
            }

            $user = $user[0];
        }

        $initVal[Track::F_ACTION] = "login";
        $initVal[Track::F_CLASS] = User::getClass();
        $initVal[Track::F_OBJECT_ID] = $user["id"];
        $initVal[Track::F_USER_ID] = $user["id"];
        if (isset($_SERVER["REMOTE_ADDR"])) {
            $ext["IP"] = $_SERVER["REMOTE_ADDR"];
        } else {
            $ext["IP"] = "not given";
        }

        if ($user[User::F_BAD_LOG] >= self::$maxBadLog) {
            $this->loginErrorMessage = "Zbyt dużo błędnych logowań.";
            return false;
        }

        if (!empty($user["password"]) && (User::comparePassword($user["password"], $password) || $loginAs) && $user["active"] == "1") {
            $ext["result"] = true;
            $initVal["info"] = serialize($ext);
            $track = new Track($initVal);
            $track->save();

            if ($loginAs) {
                $this->shadowUser = $this->user;
                $_SESSION["auth"]["shadowUser"] = serialize($this->user);
            }

            $this->user = $user;
            SessionHandler::getDefault()->assignUser($user["id"]);

            if (RequestContext::getDefault()["remember_me"]) {
                $this->setRememberCookie();
            }

            $user["bad_log"] = 0;
            $user->save();

            return true;
        } elseif ($user["active"] == "0") {
            $ext["result"] = false;
            $this->loginErrorMessage = "Konto nie aktywne.";
            return false;
        } else {
            $ext["result"] = false;
            $initVal["info"] = serialize($ext);
            $track = new Track($initVal);
            $track->save();
            $user["bad_log"] = $user["bad_log"] + 1;
            $user->save();
            $this->loginErrorMessage = "Podany login lub hasło nie zgadzają się.";
            return false;
        }
    }

    /**
     * logout
     *
     * @return void
     */

    public function doLogout()
    {
        $this->user = null;
        SessionHandler::getDefault()->clearUserConnection();
        $this->destroyRememberCookie();
    }

    /**
     * IAuth handler implementation
     *
     * @return boolean
     */

    public function isLogged()
    {
        return !is_null($this->user);
    }

    public function getLoginErrorMessage()
    {
        return $this->loginErrorMessage;
    }
}

?>
