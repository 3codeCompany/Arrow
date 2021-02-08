<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 09.12.13
 * Time: 07:16
 */

namespace Arrow\Common\Models\History;

use Arrow\Controls\api\common\Modal;
use Arrow\Controls\api\Filters\DateFilter;
use Arrow\Controls\API\FiltersPresenter;
use Arrow\Controls\API\Table\Columns\Simple;
use Arrow\Controls\API\Table\Table;
use Arrow\Controls\api\WidgetsSet;
use Arrow\ORM\ORM_Arrow_Common_Models_History;
use Arrow\ORM\ORM_Arrow_Common_Models_History_History;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\ORM_Arrow_Application_History;
use Arrow\ORM\ORM_Arrow_CRM_History;
use Arrow\ORM\Persistent\PersistentObject;
use Arrow\Access\Models\Auth;
use Arrow\Access\Models\User;
use Arrow\Common\Models\Wigets\Table\TableDataSource;

class History extends ORM_Arrow_Common_Models_History_History
{
    /**
     * @param PersistentObject $object
     * @return History[]
     */
    public static function getObjectHistory(PersistentObject $object)
    {
        return self::get()
            ->c(self::F_ELEMENT_ID, $object->getPKey())
            ->c(self::F_CLASS, $object->getClass())
            ->find();
    }

    public static function getObjectHistoryCriteria(PersistentObject $object)
    {
        return self::get()
            ->c(self::F_ELEMENT_ID, $object->getPKey())
            ->c(self::F_CLASS, $object->getClass());
    }

    public static function createEntryFlex(PersistentObject $object, $data)
    {
        $user = Auth::getDefault()->getUser();
        $base = [
            History::F_ELEMENT_ID => $object->getPKey(),
            History::F_CLASS => $object::getClass(),
            History::F_CREATED => date("Y-m-d H:i:s"),
            History::F_USER_ID => $user ? $user->_id() : "-1",
        ];

        $data = array_merge($base, $data);
        return self::create($data);
    }

    /**
     * @param PersistentObject $object
     * @param $description
     * @param bool $addData1
     * @param bool $addData2
     * @deprecated
     * @return static
     */
    public static function createEntry(PersistentObject $object, $description, $addData1 = false, $addData2 = false)
    {
        $user = Auth::getDefault()->getUser();
        $base = [
            History::F_ELEMENT_ID => $object->getPKey(),
            History::F_CLASS => $object::getClass(),
            History::F_CREATED => date("Y-m-d H:i:s"),
            History::F_USER_ID => $user ? $user->_id() : "-1",
            History::F_ACTION => $description,
            History::F_DESCRIPTION => $description,
            History::F_ADD_DATA_1 => $addData1,
            History::F_ADD_DATA_2 => $addData2,
        ];
        return self::create($base);
    }
}
