<?php namespace Arrow\Models;



class SessionHandler
{
    /**
     * Object instance keeper
     *
     * @var SessionHandler
     */
    private static $oInstance = null;

    /**
     * @var DB
     */
    private $db = null;

    private $data = null;

    private $sessionId = null;

    private $userId = null;

    public static $sessionCookieName = "session";

    /**
     * Singleton
     *
     * @return SessionHandler
     */
    public static function getDefault()
    {
        if (self::$oInstance == null) {
            self::$oInstance = new SessionHandler();
        }
        return self::$oInstance;
    }

    private function __construct()
    {

        if(getenv("APP_DISABLE_DB"))
            return;
        $this->db = Project::getInstance()->getDB();

        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );

        // the following prevents unexpected effects when using objects as save handlers
        register_shutdown_function('session_write_close');

        $time = 48 * 3600;

        ini_set("session.gc_maxlifetime", $time);
        ini_set("session.use_trans_sid", 0);
        ini_set("session.use_cookies", "1");
        ini_set("session.use_only_cookies", "1");

        session_name(self::$sessionCookieName);
        session_set_cookie_params(time() + $time, "/");

        if (isset($_REQUEST["sessionId"])) {
            session_id($_REQUEST["sessionId"]);
        }

        session_start();

    }

    public function switchSession($newSessionId)
    {
        session_id($newSessionId);
        $this->read("");
    }

    public function assignUser($userId){
        $this->userId = $userId;
        $stm = $this->db->prepare("update access_sessions set `user_id` = ? where id = ?");
        $stm->execute(array($userId, $this->sessionId));
    }

    public function clearUserConnection(){
        $stm = $this->db->prepare("update access_sessions set `user_id` = ? where id = ?");
        $stm->execute(array(null, $this->sessionId));
    }

    /**
     * @return null
     */
    public function getUserId()
    {
        return $this->userId;
    }



    public function regenerate()
    {
    }

    public function open($savePath, $sessionName)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        $sessionHash = session_id();
        if(empty($sessionHash)){
            session_id(md5(microtime().rand(1,10000)));
            $sessionHash = session_id();
        }

        //$data = $this->db->query("select id,value,user_id from access_sessions where hash='".$sessionHash."'")->fetch(\PDO::FETCH_NUM);

        $addr = isset($_SERVER['REMOTE_ADDR'])?inet_pton ($_SERVER['REMOTE_ADDR']):'NULL';
        $selectSessionStm = $this->db->prepare("select id,value,user_id from access_sessions where `hash`= ? and `ip`= ?");
        $selectSessionStm->execute(array($sessionHash, $addr));
        $data = $selectSessionStm->fetch(\PDO::FETCH_NUM);

        if (empty($data)) {
            $addr = isset($_SERVER['REMOTE_ADDR'])?inet_pton ($_SERVER['REMOTE_ADDR']):'NULL';
            $stm = $this->db->prepare("insert into access_sessions(`hash`,`last`,`ip`) values( ? , ?, ?)");
            $stm->execute(array($sessionHash, date("Y-m-d H:i:s"), $addr));
            $this->sessionId = $this->db->lastInsertId();
            $this->data = "";
        }else{
            $this->sessionId = $data[0];
            $this->data = $data[1]?$data[1]:"";
            $this->userId = $data[2];
            $stm = $this->db->prepare("update access_sessions set `last`= ? where id= ?");
            $stm->execute(array(date("Y-m-d H:i:s"), $this->sessionId));

        }
        return $this->data;
    }

    public function write($id, $data)
    {
        $stm = $this->db->prepare("update access_sessions set `value` = ? where id = ?");
        $stm->execute(array($data, $this->sessionId));
        return true;
    }

    public function destroy($id)
    {
        $stm = $this->db->prepare("delete from access_sessions where id = ?");
        $stm->execute(array( $this->sessionId));
        return true;
    }

    public function gc($maxlifetime)
    {
        $stm = $this->db->prepare("delete from access_sessions where last < ?");
        $date = date("Y-m-d H:i:s", time() - $maxlifetime);
        $stm->execute(array($date));
        return true;
    }


}

?>