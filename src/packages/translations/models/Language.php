<?php
namespace Arrow\Translations\Models;


use Arrow\ORM\ORM_Arrow_Translations_Language;

class Language extends ORM_Arrow_Translations_Language
{

    const TCLASS = __CLASS__;
    const F_ID = "id";
    const F_NAME = "name";
    const F_LONG_NAME = "long_name";
    const F_ID_PICTURE = "id_picture";
    const F_DEFAULT = "default";


//*USER AREA*//

    //private static $current_lang = null;

    private static $current_lang = null;
    private static $langs = array();

    public static function create($initialValues, $class = self::TCLASS)
    {
        $object = parent::create($initialValues, $class);
        return $object;
    }

    public function save()
    {
        if (isset($this[self::F_DEFAULT]) && $this[self::F_DEFAULT] == 1) {
            SqlRouter::query("UPDATE utils_lang SET `default` = 0 ", "cms");
        }
        parent::save();
    }

    public static function getLangName($id)
    {
        $lang = Lang::getByKey($id, Lang::TCLASS);
        return $lang["name"];
    }

    /**
     * Zwraca obecny język jako obiekty typu Lang
     */
    public static function getCurrentLang()
    {
        if (empty(self::$current_lang)) {
            if (isset($_SESSION["arrow"]["lang"])) {
                $crit = new Criteria();
                $crit->addCondition(Lang::F_NAME, $_SESSION["arrow"]["lang"]);
                $lang = Lang::getByCriteria($crit, Lang::TCLASS);
                self::$current_lang = $lang[0];
            } else { // pobierz i ustaw domyślny język
                self::$current_lang = $lang = Criteria::query('Arrow\Utils\Lang')->c('default', 1)->findFirst();
                if (empty(self::$current_lang))
                    throw new \Arrow\Exception("[Lang::getCurrentLang] Default language does not exist");
            }
        }
        return self::$current_lang;
    }

    /**
     * Ustawia język na wybrany
     * @param $lang - nazwa języka bądź obiekt języka który chcemy ustawić
     */
    public static function setCurrentLang($lang)
    {

        if (is_object($lang)) {
            $_SESSION["arrow"]["lang"] = $lang["name"];

            self::$current_lang = $lang;
        } else {
            $crit = new Criteria();
            $crit->addCondition(Lang::F_NAME, $lang);
            $l = Lang::getByCriteria($crit, Lang::TCLASS);
            if (isset($l[0])) {
                $_SESSION["arrow"]["lang"] = $l[0]["name"];
                self::$current_lang = $l[0];
                return $l[0];
            } else { // stworzenie domyślnego języka pl
                $nlang = Lang::create(array("name" => "pl", "long_name" => "Polski", "default" => 1), Lang::TCLASS);
                $nlang->save();
                //print_r($nlang);
                //exit;
                $_SESSION["arrow"]["lang"] = $nlang["name"];
                self::$current_lang = $nlang;
                return $nlang;
                //throw new \Arrow\Exception( "[Lang::setCurrentLang]Any lang does not exits" ) ;
            }
        }
    }

    /**
     * @return Lang
     */
    public static function getDefaultLang()
    {
        $crit = new Criteria();
        $crit->addCondition(Lang::F_DEFAULT, 1);
        $l = Lang::getByCriteria($crit, Lang::TCLASS);
        if (isset($l[0])) return $l[0];
        else throw new \Arrow\Exception("[Lang::getDefaultLang]Deflaut lang not exist");
    }

    public function isDefault()
    {
        if ($this[self::F_DAFAULT] == 1) return true;
        return false;
    }

    /**
     * zwraca wszystkie języki posegregowane alfabetycznie
     */
    public static function getAllLangs()
    {
        $crit = new Criteria();
        $crit->addOrderBy(Lang::F_NAME, Criteria::O_ASC);
        return Lang::getByCriteria($crit, Lang::TCLASS);
    }

    public function getName()
    {
        return $this["name"];
    }

//*END OF USER AREA*//
}

?>