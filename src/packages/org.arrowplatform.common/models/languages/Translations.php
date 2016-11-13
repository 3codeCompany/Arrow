<?php
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 26.07.13
 * Time: 17:23
 * To change this template use File | Settings | File Templates.
 */

namespace Arrow\Package\Common;


use Arrow\Models\Logger\ConsoleStream;
use Arrow\Models\Logger\Logger;
use Arrow\Models\Project;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\DataSet;
use Arrow\Package\Application\Language;
use Arrow\Package\Application\LanguageText;
use Arrow\Package\Application\ObjectTranslation;
use Arrow\Package\Application\Property;

class Translations
{
    private static $defaultLang = "pl";
    private static $defaultObjectsLang = "pl";
    private static $currLanguage = "pl";
    public static $module = null;

    public static function getLangs(){
        return ["pl" => "Polski", "en" => "Angielski", "ua" => "Ukraiński", "ru" => "Rosyjski", "de" => "Niemiecki"];
    }


    /*public static  function prt(){
        $db = Project::getInstance()->getDB();
        foreach (Language::get()->find() as $_lang) {
            $t = LanguageText::getTable();
            $q = "update {$t} set lang='{$_lang["code"]}' where lang_id={$_lang["id"]}";
            $db->exec($q);
        }
    }*/

    public static function setupLang($lang)

    {

        self::$currLanguage = $lang;
    }

    public static function getCurrentLang(){
        return self::$currLanguage;
    }

    public static function findObjectsByField( $class, $field, $value, $lang = false, $condition = Criteria::C_EQUAL ){



        $result = ObjectTranslation::get()
            ->c(ObjectTranslation::F_CLASS, $class)
            ->c(ObjectTranslation::F_FIELD, $field)
            ->c(ObjectTranslation::F_VALUE, $value, $condition)
            ->c(ObjectTranslation::F_LANG, $lang?$lang:self::$currLanguage)
            ->findAsFieldArray(ObjectTranslation::F_ID_OBJECT)
        ;



        if($result){
            return $class::get()
                ->c("id", $result, Criteria::C_IN )
                ->find()
                ;
        }

        return [];

    }

    public static function findObjectByField( $class, $field, $value, $lang = false, $condition = Criteria::C_EQUAL ){

        $result = ObjectTranslation::get()
            ->c(ObjectTranslation::F_CLASS, $class)
            ->c(ObjectTranslation::F_FIELD, $field)
            ->c(ObjectTranslation::F_VALUE, $value, $condition)
            ->c(ObjectTranslation::F_LANG, $lang?$lang:self::$currLanguage)
            ->findFirst()
        ;

        if($result){
            return $class::get()
                ->findByKey( $result[ObjectTranslation::F_ID_OBJECT] );
        }

        return null;

    }

    public static function translateText($text, $lang = false, $addidtionalData = [])
    {

        if(!$lang)
            $lang = self::$currLanguage;

        if ($lang == self::$defaultLang)
            return $text;

        //Logger::get('console',new ConsoleStream())->log($text." ".$lang);

        $result = LanguageText::get()
            ->c(LanguageText::F_HASH, md5($text))
            ->c(LanguageText::F_LANG, $lang)
            //->c(LanguageText::F_MODULE, self::$module)
            ->findFirst();
        if($result and !empty($addidtionalData)){
            $result[LanguageText::F_MODULE] = implode(",", $addidtionalData);
            $result->save();
        }

        if ($result /*&& $result["value"]*/){
            //$result[LanguageText::F_LAST_USE] = date("Y-m-d");
            //$result->save();
            //Logger::get('console',new ConsoleStream())->log( $text." ".$result["value"]);


            return $result["value"];
        }else {
            foreach (self::getLangs() as $_lang => $name) {
                if($_lang == "pl")
                    continue;
                LanguageText::create(array(
                    LanguageText::F_HASH => md5($text),
                    LanguageText::F_ORIGINAL => $text,
                    LanguageText::F_LANG => $_lang,
                    LanguageText::F_MODULE => implode(",", $addidtionalData)
                ));
            }
            //if not english and can't find in current
            if($lang != "en"){
                return self::translateText($text,"en");
            }
        }
        return $text;
    }

    public static function translateObject($object , $lang = false)
    {
        self::translateObjectsList([$object], false, $lang);
        return $object;
    }



    public static function  translateObjectsList($list, $class = false, $lang = false)
    {



        if(!$lang)
            $lang = self::$currLanguage;

        

        if ($lang == self::$defaultObjectsLang)
            return $list;




        if (empty($list)) {
            return $list;
        }
        

        //geting first element
        $first = null;
        if ($list instanceof DataSet) {
            foreach ($list as $el) {
                $first = $el;
                break;
            }
        } else {
            $first = reset($list);
        }

        //geting class if not set
        $class = $class ? $class : get_class($first);

        $fields = [];
        //geting fields
        if(is_array($first)){
            $fields = array_keys($first);
        }elseif( $first instanceof IMultilangObject ){
            $fields = array_intersect( $class::getMultiLangFields(), $first->getLoadedFields());
        }

        $keys = [-1];

        foreach ($list as $el) {
            if($el["id"])
                $keys[] = $el["id"];
        }
        $db = Project::getInstance()->getDB();

        //exit("select * from common_lang_objects_translaction where id_object in(" . implode(",", $keys) . ") and `class`='" . mysql_escape_string($class) . "' and lang='" . $lang . "' and field in('".implode("','",$fields)."')");

        $stm = $db->prepare("select * from common_lang_objects_translaction where id_object in(" . implode(",", $keys) . ") and `class`='" . mysql_escape_string($class) . "' and lang='" . $lang . "' and field in('".implode("','",$fields)."') order by value desc");


        try{
            $stm->execute();
        }catch (\PDOException $ex){
            exit("select * from common_lang_objects_translaction where id_object in(" . implode(",", $keys) . ") and `class`='" . mysql_escape_string($class) . "' and lang='" . $lang . "' and field in('".implode("','",$fields)."')  order by value desc");
        }
        $data = array();
        while ($row = $stm->fetch(\PDO::FETCH_ASSOC)) {
            $data[$row["id_object"]][$row["field"]] = $row["value"];
        }
        //in case of empty value we taking en language
        $secondLoad = [ "objects" => [] , "fields" => []];

        foreach($list as $el){
            if(!isset( $data[$el["id"]])) {
                self::putEmptyObjectTranslation($el);
            }

            foreach($fields as $field){
                if(!isset( $data[$el["id"]][$field]) || empty($data[$el["id"]][$field]) ){
                    if($el["id"]) {
                        $secondLoad["objects"][] = $el["id"];
                        $secondLoad["fields"][] = $field;
                    }

                }

            }
        }
        if(!empty($secondLoad["objects"])){
            $query = "select * from common_lang_objects_translaction where id_object in(" . implode(",", $secondLoad["objects"]) . ") and `class`='" . mysql_escape_string($class) . "' and lang='" . "en" . "' and field in('".implode("','",$secondLoad["fields"])."') ";
            $stm = $db->prepare($query);

            try{
                $stm->execute();
            }catch (\PDOException $ex){
                exit($query);
            }
            while ($row = $stm->fetch(\PDO::FETCH_ASSOC)) {
                if(!isset($data[$row["id_object"]][$row["field"]]) || empty($data[$row["id_object"]][$row["field"]])){
                    $data[$row["id_object"]][$row["field"]] = $row["value"];
                }
            }
        }



        foreach ($list as $key => $el) {

            if (isset($data[$el["id"]])) {
                foreach ($data[$el["id"]] as $field => $val) {
                    if (isset($el[$field]) && $val ){
                        if(is_array($list)){
                            $list[$key][$field] = $val;
                        }else{
                            $el[$field] = $val;
                        }
                    }else{
                        //$el[$field] = "";
                    }
                }
            }else{
                /*foreach($fields as $field)
                    $el[$field] = "";*/
            }
        }


        return $list;

    }
    public static function putEmptyObjectTranslation($obiect){

        if(!$obiect)
            return;
        $langFields = $obiect::getMultiLangFields();
        foreach ($langFields as $field ) {
            ObjectTranslation::createIfNotExists([
                ObjectTranslation::F_CLASS => $obiect::getClass(),
                ObjectTranslation::F_ID_OBJECT => $obiect->getPKey(),
                ObjectTranslation::F_LANG => self::$currLanguage,
                ObjectTranslation::F_FIELD => $field,
                ObjectTranslation::F_VALUE => "",
                ObjectTranslation::F_SOURCE => $obiect[$field]!=null?$obiect[$field]:"" 
            ]);
        }
    }

    public static function saveObjectTranslation($obiect, $data)
    {
        $fields = array_keys($data);
        $query = "delete from common_lang_objects_translaction where field in('" . implode("','", $fields) . "') and class='" . addslashes(get_class($obiect)) . "' and lang='" . self::$currLanguage . "' and id_object=" . $obiect->getPKey();
        $db = Project::getInstance()->getDB();
        $db->exec($query);


        $class = get_class($obiect);

        $langFields = $class::getMultiLangFields();

        foreach ($data as $field => $value) {
            if(!in_array($field,$langFields))
                continue;
            $query = "insert into common_lang_objects_translaction (field, id_object,lang,value, class) values('" . $field . "','" . $obiect->getPKey() . "','" . self::$currLanguage . "','" . mysql_escape_string($value) . "', '" . addslashes(get_class($obiect)) . "')";
            $db->exec($query);
        }

    }

    /**
     * @return string
     */
    public static function getDefaultLang()
    {
        return self::$defaultLang;
    }

    /**
     * @param string $defaultLang
     */
    public static function setDefaultLang($defaultLang)
    {
        self::$defaultLang = $defaultLang;
    }

    /**
     * @return string
     */
    public static function getDefaultObjectsLang()
    {
        return self::$defaultObjectsLang;
    }

    /**
     * @param string $defaultObjectsLang
     */
    public static function setDefaultObjectsLang($defaultObjectsLang)
    {

        self::$defaultObjectsLang = $defaultObjectsLang;
    }


}