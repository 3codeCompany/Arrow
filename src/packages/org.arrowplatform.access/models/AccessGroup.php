<?php
namespace Arrow\Package\Access;
use Arrow\ORM\PersistentObject,
    Arrow\ORM\SqlRouter;
use Arrow\Package\Utils\Developer;

/**
 * Arrow access group class
 *
 * @version 1.0
 * @license  GNU GPL
 * @author 3code Team
 */
class AccessGroup extends \Arrow\ORM\ORM_Arrow_Package_Access_AccessGroup
{

    const DEVELOPERS = "Developers";
    const ADMINISTRATORS = "Administrators";

    public function beforeObjectCreate(PersistentObject $object)
    {
        /**
         * Id are power of 2
         */
        if ($object->getPKey() == null) {
            $db = \Arrow\Models\Project::getInstance()->getDB();
            $st = $db->query("select max(" . self::getPKField() . ") as max from " . self::getTable());
            $result = $st->fetchColumn();
            $id = empty($result) ? 1 : $result * 2;
            $this->setValue(self::getPKField(), $id);
        }
    }


    public function beforeObjectDelete(PersistentObject $object)
    {
        $userCon = \Arrow\ORM\Persistent\Criteria::query('\Arrow\Package\Access\AccessUserGroup')->c("group_id", $this->getPKey())->find();
        foreach($userCon as $c) $c->delete();

        $pointCon = \Arrow\ORM\Persistent\Criteria::query('\Arrow\Package\Access\AccessPointGroup')->c("group_id", $this->getPKey())->find();
        foreach($pointCon as $c) $c->delete();

        //remove group from access control
        $q = "update ".AccessPoint::getTable()." set ".AccessPoint::F_GROUPS."=".AccessPoint::F_GROUPS."-".$this->getPKey()." where ".AccessPoint::F_GROUPS." & ".$this->getPKey();
        $db = \Arrow\Models\Project::getInstance()->getDB();
        $db->exec($q);

        parent::beforeObjectDelete($object);
    }



}

?>