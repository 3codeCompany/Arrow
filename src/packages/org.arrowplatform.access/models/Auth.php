<?php
namespace Arrow\Access;
use Arrow\Exception;
use Arrow\Models\SessionHandler;
use \Arrow\ORM\Persistent\Criteria, \Arrow\Package\Common\Track;
use Arrow\ORM\DB\DB;
use Arrow\RequestContext;
use Arrow\Router;


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


    private $reason = "";

    private static $rememberCookieName = "arrow_access_remember_key";

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
        if($id)
            $this->user = User::get()->findByKey($id);


        if (isset($_SESSION["auth"]["shadowUser"]) && is_string($_SESSION["auth"]["shadowUser"])) {
            $this->shadowUser = unserialize($_SESSION["auth"]["shadowUser"]);
        }

        if(is_null($this->user))
            $this->loginByRememberCookie();
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
     * @return \Arrow\Access\User
     */
    public function getShadowUser()
    {
        return $this->shadowUser;
    }

    public function setRememberCookie(){
        if($this->isLogged()){
            $this->user["remember_key"] = md5(microtime());
            $this->user->save();
            $key = substr($this->user["login"],0,2)."_".$this->user["remember_key"];
            setcookie(self::$rememberCookieName, $key, time()+3600*24*7, Router::getBasePath() );
        }else{
            throw new Exception("User not logged");
        }
    }

    private function loginByRememberCookie(){
        if( !isset($_COOKIE[self::$rememberCookieName]) )
            return false;

        $str = $_COOKIE[self::$rememberCookieName];
        $hash = substr($str,3);
        $loginPart = substr($str,0, 2);



        $user = Criteria::query(User::getClass())
            ->c("remember_key", $hash)
            ->findFirst();

        if(!$user || substr($user["login"],0,2) != $loginPart)
            return false;
        else{
            $this->user = $user;
            SessionHandler::getDefault()->assignUser($user["id"]);
            $this->setRememberCookie();
            return true;
        }


    }

    private function destroyRememberCookie(){
        unset($_COOKIE[self::$rememberCookieName]);
        return setcookie(self::$rememberCookieName, NULL, -1, Router::getBasePath());
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
     * @return boolean
     */

    public function doLogin($login, $password, $loginAs = false, $user = false)
    {

        if(!$user){
            $result = Criteria::query('\Arrow\Access\User')
                ->c("login", $login)
                ->find();


            $query = DB::getDB()->getLastQuery();
            $result = $result->toArray();
            if (count($result) > 1){
                throw new \Arrow\Exception( [ "msg" => "Istnieją identyczne loginy", "query" => $query ]);
            }

            if (count($result) == 0) {
                $this->reason = "Podany login lub hasło nie zgadzają się.";
                return false;
            }

            $result = $result[0];
        }else{
            $result = $user;
        }



        $initVal[Track::F_ACTION] = "login";
        $initVal[Track::F_CLASS] = 'Arrow\Access\User';
        $initVal[Track::F_OBJECT_ID] = $result["id"];
        $initVal[Track::F_USER_ID] = $result["id"];
        if (isset($_SERVER["REMOTE_ADDR"]))
            $ext["IP"] = $_SERVER["REMOTE_ADDR"];
        else
            $ext["IP"] = "not given";

        // Sprawdzenie ilości błędnych logowań
        $limit_bad_log = 10;

        if ( false && $limit_bad_log <= (int)$result["bad_log"]) {
            $ext["result"] = false;
            $initVal["info"] = serialize($ext);
            AccessManager::turnOff();
            $track = new Track($initVal);
            $track->save();
            $result["bad_log"] = $result["bad_log"] + 1;
            $result->turnOff();
            $result->save();
            AccessManager::turnOn();
            $this->reason = "Konto zostało zablokowane";
            return false;
        }


        if (!empty($result["password"]) && (User::comparePassword($result["password"], $password) || $loginAs) && $result["active"] == "1") {

            $ext["result"] = true;
            $initVal["info"] = serialize($ext);
            AccessManager::turnOff();
            $track = new Track($initVal);
            $track->save();
            $result["bad_log"] = 0;
            $result->save();
            AccessManager::turnOn();

            if($loginAs){
                $this->shadowUser = $this->user;
                $_SESSION["auth"]["shadowUser"] = serialize($this->user);
            }

            $this->user = $result;
            SessionHandler::getDefault()->assignUser($result["id"]);

            if (RequestContext::getDefault()["remember_me"]) {
                $this->setRememberCookie();
            }


            return true;
        } elseif ($result["active"] == "0") {
            $ext["result"] = false;
            $this->reason = "Konto nie aktywne.";
            return false;
        } else {
            $ext["result"] = false;
            $initVal["info"] = serialize($ext);
            AccessManager::turnOff();
            $track = new Track($initVal);
            $track->save();
            $result["bad_log"] = $result["bad_log"] + 1;
            $result->save();
            AccessManager::turnOn();
            $this->reason = "Podany login lub hasło nie zgadzają się.";
            return false;
        }

    }

    public function refreshUserData(){
        $this->user = $result = Criteria::query('\Arrow\Access\User')
            ->c("id", $this->user->getPKey())
            ->findFirst();
        $_SESSION["auth"]["user"] = serialize($this->user);
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


    public function getReason()
    {
        return $this->reason;
    }
}

?>