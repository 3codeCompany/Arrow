<?php
namespace Arrow\Access\Models;

use Arrow\Exception;
use Arrow\Kernel;
use Arrow\Models\DB;
use Arrow\Models\Project;
use Arrow\ORM\ORM_Arrow_Access_Models_User;
use \Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\Persistent\JoinCriteria;
use Arrow\ORM\Persistent\PersistentObject;

class User extends ORM_Arrow_Access_Models_User
{

    /**
     * Cached access groups ( groupId=>groupName ) loaded after object load
     * @var Array
     */
    private $accessGroups = null;

    /**
     * Sum of access groups id
     * @var int
     */
    private $accessGroupsSum = null;


    /**
     * Before create object generate it's safe id ( passport_id )
     * @param \Arrow\ORM\PersistentObject $object
     */
    public function beforeObjectCreate(PersistentObject $object)
    {
        if ($object["password"] == "") {
            $this->setValue("password", md5(microtime() . rand(1, 100)));
        }
        //$this->setValue(self::F_CREATED, date("Y-m-d H:i:s"));
        $this->setValue(self::F_PASSPORT_ID, self::generatePassportId());
        $this->setValue(User::F_CREATED, date("Y-m-d H:i:s"));

        parent::beforeObjectCreate($object);
    }

    /**
     * If password field is modified it will be changed to password hash
     * @param \Arrow\ORM\PersistentObject $object
     * @param $field
     * @param $oldValue
     * @param $newValue
     */
    public function fieldModified(PersistentObject $object, $field, $oldValue, $newValue)
    {
        if ($field == self::F_PASSWORD) {
            if (!empty($newValue)) {
                $this->data[self::F_PASSWORD] = self::generatePassword($newValue);
                $this["password_changed"] = date("Y-m-d");
            } else {
                $this->data[self::F_PASSWORD] = $oldValue;
            }
        }
    }

    /**
     * If access groups parameter is given object append new groups
     * @param \Arrow\ORM\PersistentObject $object
     */
    public function afterObjectSave(PersistentObject $object)
    {
        if (isset($this->parameters["accessGroups"])) {
            if (is_array($this->parameters["accessGroups"])) {
                foreach ($this->parameters["accessGroups"] as $key => $val) {
                    if (empty($val)) {
                        unset($this->parameters["accessGroups"][$key]);
                    }
                }

                $this->setGroups($this->parameters["accessGroups"]);
            } elseif (!empty($this->parameters["accessGroups"])) {
                $this->setGroups([$this->parameters["accessGroups"]]);
            }
        }

        parent::afterObjectSave($object);
    }

    /**
     * Delete groups connection
     * @param \Arrow\ORM\PersistentObject $object
     */
    public function beforeObjectDelete(PersistentObject $object)
    {
        $this->setGroups(array());
        parent::beforeObjectDelete($object);
    }


    /**
     * Sets user access groups
     * @param $groups Array Array of groups Id
     */
    public function setGroups($groups)
    {
        $res = AccessUserGroup::get()
            ->c("user_id", $this->getPKey())
            ->find();

        foreach ($res as $r) {
            $r->delete();
        }

        foreach ($groups as $gr) {
            $g = new AccessUserGroup(array(
                AccessUserGroup::F_USER_ID => $this->getPKey(),
                AccessUserGroup::F_GROUP_ID => $gr
            ));
            $g->save();
        }
    }

    private function loadAccessGroups()
    {
        /** @var DB $db */
        //$db = Kernel::getProject()->getContainer()->get(DB::class);
        $db = Project::getInstance()->getDB();

        $this->accessGroupsSum = $db->query("select sum(DISTINCT group_id) from " . AccessUserGroup::getTable() . " where user_id=" . $this->getPKey())->fetchColumn();

        $this->accessGroups = AccessGroup::get()
            ->_id($this->accessGroupsSum, Criteria::C_BIT_AND)
            ->findAsFieldArray("name");

    }

    /**
     * Return array of user access groups groupId=>groupName
     * @return Array
     */
    public function getAccessGroups()
    {
        if ($this->accessGroups === null) {
            $this->loadAccessGroups();
        }
        return $this->accessGroups;
    }

    /**
     * Checks that user is in access group by group name given
     * @param $groupName
     * @return bool
     */
    public function isInGroup($groupName)
    {
        if (is_array($groupName)) {

            foreach ($groupName as $name) {
                if ($this->isInGroup($name)) {
                    return true;
                }
            }
            return false;
        }
        if ($this->accessGroups === null) {
            $this->loadAccessGroups();
        }
        return in_array($groupName, $this->accessGroups);
    }

    /**
     * Returns sum of access groups ids
     * @return int
     */
    public function getAccessGroupsSum()
    {
        if ($this->accessGroupsSum === null) {
            $this->loadAccessGroups();
        }

        return $this->accessGroupsSum;
    }

    /**
     * Generates user passport id (safe id)
     * @return string
     */
    public function generatePassportId()
    {
        return md5((isset($this->data["login"]) ? $this->data["login"] : rand(100, 10000)) . time());
    }

    /**
     * Generates user password
     * @static
     * @param $pass
     * @return string
     */
    public static function generatePassword($pass)
    {
        //return $pass;
        return md5($pass);//crypt($pass, microtime());
        //return crypt($pass, microtime());
    }

    /**
     * Compares given password with user password
     * @static
     * @param $storedPassHash
     * @param $userInput
     * @return bool
     */
    public static function comparePassword($storedPassHash, $userInput)
    {
        if ($storedPassHash == md5($userInput)) {
            return true;
        }

        if (crypt($userInput, $storedPassHash) == $storedPassHash) {
            return true;
        }
        return false;
    }

    /**
     * Returns user key
     * @return int
     */
    public function getKey()
    {
        return $this->getPKey();
    }

    /**
     * Turns user account off
     */
    public function turnOff()
    {
        $this["active"] = 0;
        $this->save();
    }

    /**
     * Turns user account on
     */
    public function turnOn()
    {
        $this["active"] = 1;
        $this->save();
    }

    /**
     * Returns user account active state
     * @return bool
     */
    public function isTurnOff()
    {
        return $this[self::F_ACTIVE] ? false : true;
    }

    /**
     * Returns friendly name for User object
     *
     * @return string
     */
    public function getFriendlyName()
    {
        return $this["login"];
    }


    public static function findByGroupId($groupId)
    {
        if (!is_array($groupId)) {
            $groupId = [$groupId];
        }
        $r = Criteria::query(AccessUserGroup::getClass())
            ->c(AccessUserGroup::F_GROUP_ID, $groupId, Criteria::C_IN)
            ->findAsFieldArray(AccessUserGroup::F_USER_ID);

        return Criteria::query(static::getClass())
            ->c(User::F_ID, $r, Criteria::C_IN)
            ->find();

    }

    private $settings = null;

    public function getSetting($name, $default = null)
    {
        if ($this->settings == null) {
            if ($this->_settings()) {
                try {
                    $this->settings = unserialize($this->_settings());
                } catch (\Exception $exception) {
                    $this->settings = [];
                }
            } else {
                $this->settings = [];
            }
        }

        if (isset($this->settings[$name])) {
            return $this->settings[$name];
        }

        return $default;
    }

    public function setSetting($name, $value)
    {
        if ($this->settings == null) {
            if ($this->_settings()) {
                $this->settings = unserialize($this->_settings());
            } else {
                $this->settings = [];
            }
        }
        $this->settings[$name] = $value;
        $this[self::F_SETTINGS] = serialize($this->settings);
        $this->save();

    }


}

?>
