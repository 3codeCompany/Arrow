<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 2014-05-12
 * Time: 19:11
 */

namespace Arrow\CMS\Models\Persistent;


use Arrow\ORM\ORM_Arrow_CMS_Models_Persistent_Banner;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\Extensions\Sortable;
use Arrow\ORM\Persistent\PersistentFactory;

class Banner extends ORM_Arrow_CMS_Models_Persistent_Banner {
    use Sortable;

    public function moveUp()
    {
        $this->updateSorting();

        $thisSort = $this[self::$EXTENSION_SORTABLE_FIELD];
        if(!$thisSort)
            $thisSort = $this->getPKey();

        $class = static::getClass();
        $prev = Criteria::query($class)
            ->c(self::$EXTENSION_SORTABLE_FIELD, $thisSort, Criteria::C_LESS_THAN)
            ->c(self::F_PLACE, $this->_place())
            ->c(self::F_LANG, $this->_lang())
            ->order(self::$EXTENSION_SORTABLE_FIELD, Criteria::O_DESC)
            ->findFirst();
        if(!$prev)
            return;

        $prevSort = $prev[self::$EXTENSION_SORTABLE_FIELD];

        $prev[self::$EXTENSION_SORTABLE_FIELD] = $thisSort;
        $this[self::$EXTENSION_SORTABLE_FIELD] = $prevSort;

        PersistentFactory::save($prev, false);
        $this->save();
        return $this;
    }

    public function moveDown()
    {

        $this->updateSorting();

        $thisSort = $this[self::$EXTENSION_SORTABLE_FIELD];
        if(!$thisSort)
            $thisSort = $this->getPKey();

        $class = static::getClass();
        $prev = Criteria::query($class)
            ->c(self::$EXTENSION_SORTABLE_FIELD, $thisSort, Criteria::C_GREATER_THAN)
            ->c(self::F_PLACE, $this->_place())
            ->c(self::F_LANG, $this->_lang())
            ->order(self::$EXTENSION_SORTABLE_FIELD, Criteria::O_ASC)
            ->findFirst();
        if(!$prev)
            return;

        $prevSort = $prev[self::$EXTENSION_SORTABLE_FIELD];

        $prev[self::$EXTENSION_SORTABLE_FIELD] = $thisSort;
        $this[self::$EXTENSION_SORTABLE_FIELD] = $prevSort;

        PersistentFactory::save($prev, false);
        $this->save();
        return $this;
    }


} 