<?php
namespace Arrow\Package\CMS;

use \Arrow\ORM\Persistent\Criteria, Arrow\Models\TemplateLinker;
use Arrow\ORM\Extensions\TreeNode;
use Arrow\Package\Access\Auth;
use Arrow\Package\Access\User;
use Arrow\Package\Common\IMultilangObject;
use Arrow\Router;

;

class Page extends \Arrow\ORM\ORM_Arrow_Package_CMS_Page implements IMultilangObject
{
    use TreeNode{
        getChildren as _getChildren;
    }


    public static function getMultiLangFields(){
        return array("name", "content", "header", "title", "description","keywords");
    }


    public function setValue($key, $value, $tmp = false)
    {

        if ($key == "places") {
            $page_places = $this->getPlaces();
            foreach ($value as $id => $placeData) {
                $place_ok = false;
                foreach ($page_places as $key => $place) {
                    if (is_array($value)) {
                        if ($id == $place["id"]) {
                            $place->setValues($placeData);
                            $place_ok = true;
                        }
                    } else
                            {
                                throw new \Arrow\Exception(array('msg' => '[Page] Incorrect placeData in setValue ',));
                            }
                }
                //if (!$place_ok)
                //throw new OrmException(array('msg'=>'[Page] Incorrect place name', 'name'=>$name ));
            }
            return;
        }
        parent::setValue($key, $value, $tmp);
    }

    public static function parseContent($source)
    {
        //detect images
        preg_replace_callback(
            '/<img.+?src="(.+?)".+?>/', function ($matches) {
                $el = simplexml_load_string($matches[0]);
                $at = (array)$el[0]->attributes();
                $at = $at["@attributes"];

                $src = false;
                if (isset($at["src"])) $src = $at["src"];
                if (isset($at["data-org"])) $src = $at["src"];

                if (isset($src)) {
                    $src = str_replace($_SERVER["HTTP_HOST"], ".", $src);
                    $src = str_replace(array("http://", "https://",Router::getBasePath()), "", $src);
                    if (is_readable($src)) {
                        print_r($at);
                        $width = $height = null;
                        if(isset($at["style"])){
                            preg_match_all("/(width|height):[ ].*?([0-9]+?)px;/", $at["style"], $styleMatches,  PREG_SET_ORDER);
                            foreach($styleMatches as $m){
                                if($m[1] == "width") $width = $m[2];
                                if($m[1] == "height") $height = $m[2];
                            }
                        }

                        if(isset($at["width"]))
                            $width = $at["width"];

                        if(isset($at["height"]))
                            $height = $at["height"];

                        print_r("aaa");



                    }
                }
            }, $source
        );

    }


    /**
     * (non-PHPdoc)
     *
     * @see models/abstract/TreeNode#getChildren()
     */
    public function getChildren($id_only = false, $active = -1)
    {
        $tmp = array();
        if ($active === -1)
            $tmp = $this->_getChildren($id_only);
        if ($active === true) {
            $tmp = array();
            foreach ( $this->_getChildren($id_only) as $child) {
                if ($child[self::F_ACTIVE] == "1")
                    $tmp[] = $child;
            }
        }
        if ($active === false) {
            $tmp = array();
            foreach ( $this->_getChildren() as $child) {
                if ($child[self::F_ACTIVE] == "0")
                    $tmp[] = $child;
            }
        }

        if ($id_only == true) {
            foreach ($tmp as $key => $val) {
                if (is_object($val))
                    $tmp[$key] = $val->getPKey();
            }
        }
        return $tmp;
    }



    public function toString($extended_info = false)
    {
        if (!$extended_info)
            return $this[self::F_ID] . ": " . $this[self::F_NAME];
        else
            return parent::toString($extended_info);
    }

    public function serialize()
    {

        $arr = $this->data;
        $arr["modules"] = array();
        //foreach($this->getPlaces() as $place){
        //$arr["modules"] = array_merge($arr["modules"], $place->getModules());

        //}
        $linker = TemplateLinker::getDefault();
        foreach ($arr["modules"] as $key => $module) {
            if (is_object($arr["modules"][$key])) {
                $arr["modules"][$key]->setValue(
                    "real_link", $linker->generateTemplateLink(
                        array(
                             "path" => $module["page_admin_template"], "page_id" => $this["id"], "_htmlAmp" => false
                        )
                    ), true
                );
            }
        }

        $arr["path"] = $this->getPath(false, "_") . "_" . $arr["id"];
        return $arr;
    }





    protected function isLangObject()
    {
        return true;
    }


}

?>