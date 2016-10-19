<?php namespace Arrow\Models;
namespace Arrow\Models;
/**
 * Constant - ver 0.7 : 29.IV.2009r;
 * Obsługuje stałe projektu przechowiwane w jego konfiguracji /projects/nazwa projektu/conf/project-conf.xml
 * Obsługa poprzez wykorzystanie funkcji projektu server/standardModels/org/arrowplatform/engine/projects/ArrowProject.php
 *
 * Jeśli grupa jest 0 to zarządzać tą stałą może tylko developer
 *
 */


final class Settings implements \Arrow\Models\ISingleton, \Arrow\ICacheable
{

    /**
     * Array of all settings loaded from XML
     * @var array
     */
    private $data = [];

    /**
     * Array of all settings loaded from local data file
     * @var array
     */
    private $localData = [];

    /**
     * Path to file with local settings
     * @var string
     */
    private $localSettingsFilePath = "";

    /**
     * Default instance
     * @var object
     */
    private static $instance = null;


    public static function init($data)
    {
        $instance = self::getDefault();
        $instance->data = $data;
        $instance->readLocalData();
        return $instance;
    }

    /**
     * Returns default (dummy) setting object which can be used to access settings
     * @return \Arrow\Models\Settings
     */
    public static function getDefault()
    {
        if (self::$instance == null)
            self::$instance = new Settings();
        return self::$instance;
    }

    protected function __construct()
    {
        $this->localSettingsFilePath = Project::getInstance()->getPath() . "/data/local-settings.php";
    }

    protected function readLocalData()
    {
        if (file_exists($this->localSettingsFilePath)) {
            include $this->localSettingsFilePath;

            $this->data = $this->mergeArrays( $this->data, $localSettings);
            $this->localData = $localSettings;
            /*print "<pre>";
            print_r($this->localData);
            print "<pre>";
            print_r($this->data);*/
        }
    }


    public function parseSetingsXML($xmlSections, $packageName)
    {
        return array($packageName => $this->generateCache($xmlSections));
    }

    /**
     * Cache generation
     * @see \Arrow\ICacheable::generateCache()
     */
    public function generateCache($xmlSections)
    {
        $ret = array();

        $runConfiguration = \Arrow\Controller::getRunConfiguration();

        foreach ($xmlSections as $xmlSection) {

            //$sectionName = "".$xmlSection["name"];
            //print $sectionName;
            foreach ($xmlSection->setting as $setting) { // dla każdej stałej

                if (isset($setting["run-configuration"]) && $runConfiguration != (string)$setting["run-configuration"]) {
                    continue;
                }

                $type = "text";
                $type = (string)(isset($setting["type"]) ? $setting["type"] : $type);
                $type = (string)(isset($setting->type) ? $setting->type : $type);
                $value = (string)(isset($setting["value"]) ? $setting["value"] : $setting->value);
                $name = (string)(isset($setting["name"]) ? $setting["name"] : $setting->name);

                //todo sprawdzic czemu nie true
                if($value === "true")
                    $value = true;
                if($value === "false")
                    $value = false;

                $val = array();
                $cnst = explode('.', $name); // umiesc ją odpowiednio w tablicy
                $parent = &$ret;
                foreach ($cnst as $c) {
                    if (!isset($parent[$c])) {
                        $parent[$c] = array();
                    }
                    $parent = &$parent[$c];
                }

                if ($type == "array")
                    $parent = explode("|", $value);
                else
                    $parent = $value;

            }
        }

        return $ret;
    }

    public function getSetting($path)
    {

        if ($path == "")
            return $this->data;

        $levels = explode(".", $path);

        $el = $this->data;
        foreach ($levels as $level) {
            if (isset($el[$level]))
                $el = $el[$level];
            else
                throw new \Arrow\Exception(array("msg" => "[Setting::getSetting] Setting doesn't exist [$path] "));
        }
        if ($el == "false")
            $el = false;
        return $el;
    }

    public function getConfiguration()
    {
        $settings = array();

        /*$this->xmlDoc = $this->projectReference->getXMLConfig(Project::PROJECT_CONF_FILE);
        $settings = $this->xmlDoc->xpath("/project/settings/section/setting");*/
        $tmp = array();

        $runConfiguration = \Arrow\Controller::getRunConfiguration();


        foreach (\Arrow\Controller::$project->getPackages() as $namespace => $package) {

            if ($namespace == "application")
                $settingsFile = $package["dir"] . "/conf/project-conf.xml";
            else
                $settingsFile = $package["dir"] . "/conf/settings.xml";

            if (file_exists($settingsFile)) {
                $xmlDoc = simplexml_load_file($settingsFile);
                $settings = $xmlDoc->xpath("//settings/section/setting");
                foreach ($settings as $setting) {
                    $name = (string)(isset($setting["name"]) ? $setting["name"] : $setting->name);
                    if (isset($setting["run-configuration"]) && $runConfiguration != (string)$setting["run-configuration"]) {
                        continue;
                    }
                    $tmp[$package["namespace"]][$name] = $this->getSettingExtentdedData($setting);
                    $tmp[$package["namespace"]][$name]["package"] = $namespace;
                    $tmp[$package["namespace"]][$name]["value"] = $this->getSetting($namespace.".".$name);
                }
            }

        }

        return $tmp;
    }

    public function getSettingExtentdedData($setting)
    {
        $val = array();
        $setting = $setting[0];
        $parent = $setting->xpath('parent::*');

        $val["type"] = "text";
        $val["type"] = (string)isset($setting["type"]) ? $setting["type"] : $val["type"];
        $val["type"] = (string)isset($setting->type) ? $setting->type : $val["type"];

        $val["defaultValue"] =  (string)isset($setting["value"]) ? $setting["value"] : $setting->value;


        $val["name"] = (string)isset($setting["name"]) ? $setting["name"] : $setting->name;

        if (isset($setting["editable"]))
            $val["editable"] = (string)$setting["editable"];
        elseif (isset($setting->editable))
            $val["editable"] = (string)$setting->editable; else
            $val["editable"] = true;

        if (isset($setting["title"]))
            $val["title"] = (string)$setting["title"];
        elseif (isset($setting->title))
            $val["title"] = (string)$setting->title; else
            $val["title"] = "";

        if (isset($setting["description"]))
            $val["description"] = (string)$setting["description"];
        elseif (isset($setting->title))
            $val["description"] = (string)$setting->description; else
            $val["description"] = "";

        $val["section"] = (string)$parent[0]["name"];


        if (isset($setting->options)) {
            $val["options"] = array();
            foreach ($setting->options->option as $option) {
                $val["options"][(string)$option["value"]] = (string)$option["label"];
            }
        }


        return $val;
    }


    public function setSettingValue($package, $name, $val, $local = true)
    {
        //print $this->getSetting($package . "." . $name);
        if ($this->getSetting($package . "." . $name) == $val){
            return;
        }

        if ($local) {
            //write to local storage file
            $localSettings = array();
            if (file_exists($this->localSettingsFilePath))
                include $this->localSettingsFilePath;

            $tmp = explode(".", $name);
            $currReference = &$localSettings[$package];
            foreach ($tmp as $index => $key) {
                if(!isset($currReference[$key]))
                    $currReference[$key] = array();
                if($index+1 == count($tmp)){

                    $currReference[$key] = $val;
                }

                $currReference = &$currReference[$key];
            }

            $result = \Arrow\CacheProvider::array2File($localSettings, "localSettings");
            //exit($result);
            file_put_contents($this->localSettingsFilePath, $result);
        } else {
            //writes to global config ( xml files )


            $tmp = \Arrow\Controller::$project->getPackages();
            $package = $tmp[$package];
            if ($package["namespace"] == "application")
                $settingsFile = $package["dir"] . "/conf/project-conf.xml";
            else
                $settingsFile = $package["dir"] . "/conf/settings.xml";

            $doc = simplexml_load_file($settingsFile);

            $setting = $doc->xpath("//settings/section/setting[name='$name']");

            if (empty($setting))
                $setting = $doc->xpath("//settings/section/setting[@name='$name']");

            if (empty($setting))
                throw new \Arrow\Exception(array("msg" => "Setting not found.", "name" => $name));


            if (count($setting) > 1) {
                $runConfiguration = \Arrow\Controller::getRunConfiguration();
                foreach ($setting as $key => $s) {
                    if (isset($s["run-configuration"]) && $runConfiguration == (string)$s["run-configuration"]) {
                        $setting = $s;
                        break;
                    }
                }
            } else {
                $setting = $setting[0];
            }

            if (is_array($val))
                $val = implode("|", $val);
            if (isset($setting->value))
                $setting->value = $val;
            elseif (isset($setting["value"]))
                $setting["value"] = $val;


            $dom = new \DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($doc->asXml());
            exit($dom->saveXML());
            file_put_contents($settingsFile, $dom->saveXML());

        }
    }

    public static function validate($type, $value)
    {
        $ret = false;
        switch ($type) {
            case "number":
            {
                $ret = is_numeric($value);
            }
                break;
        }
        return $ret;
    }

    /**
     * Merge arrays
     * From php.net comments
     * @return array
     */
    private function mergeArrays($Arr1, $Arr2)
    {
        foreach($Arr2 as $key => $Value)
        {
            if(is_array($Value) && array_key_exists($key, $Arr1) )
                $Arr1[$key] = $this->MergeArrays($Arr1[$key], $Arr2[$key]);
            else
                $Arr1[$key] = $Value;
        }
        return $Arr1;
    }

}

?>