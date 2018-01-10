<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 30.11.13
 * Time: 08:16
 */

namespace Arrow\CMS\Models\Persistent;


use Arrow\ORM\Extensions\TreeNode;


use Arrow\ORM\ORM_Arrow_CMS_Models_Persistent_Page;
use Arrow\Translations\Models\IMultilangObject;

class Page extends ORM_Arrow_CMS_Models_Persistent_Page
    implements IMultilangObject
{
    use TreeNode;

    const TYPE_FOLDER = "folder";
    const TYPE_PAGE = "page";
    const TYPE_LINK = "link";
    const TYPE_INLINE_CODE = "inline_code";

    public static function getMultiLangFields()
    {
        return [
            Page::F_NAME,

            Page::F_CONTENT,
            PAGE::F_CONTENTS_ADDITIONAL
        ];
    }

    public function getLink()
    {
        if ($this->_type() == Page::TYPE_LINK) {
            return $this->_link();
        }

        return "s," . $this->_link();
    }


}
