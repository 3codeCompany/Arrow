<?php

namespace Arrow;

use Arrow\Models\Project;
use Symfony\Component\Yaml\Yaml;

class ConfigProvider extends Object
{

    private static $cacheFile = "";
    private static $conf = array();
    private static $refreshConfFile = false;
    private static $writeConfFile = true;
    private static $cacheMkTime = false;


    public static function setWriteConfFile($writeConfFile)
    {
        self::$writeConfFile = $writeConfFile;
    }


    public static function get($index = null)
    {
        if ($index == null)
            return self::$conf["project"];
        return self::$conf["project"][$index];
    }

    public static function init()
    {
        $configFile = ARROW_APPLICATION_PATH."/conf/project.yaml";
        self::$cacheFile = ARROW_CACHE_PATH . "/cached-conf.php";


        if (
            file_exists(self::$cacheFile) &&
            filemtime($configFile) < filemtime(self::$cacheFile)
        ) {
            self::$conf = unserialize(file_get_contents(self::$cacheFile));
            self::$cacheMkTime = filemtime(self::$cacheFile);
        } else {
            self::$conf = Yaml::parse(file_get_contents($configFile));

            /*foreach (Project::getInstance()->getPackages() as $package) {
                if (!file_exists($package["dir"] . "/conf/project.yaml")) {
                    continue;
                }
                $data = Yaml::parse(file_get_contents($package["dir"] . "/conf/project.yaml"));
                self::$conf = array_merge_recursive (self::$conf, $data);
            }*/

            $runConfig = Controller::getRunConfiguration();

            if (isset(self::$conf["project"]["run-config"][$runConfig])) {
                foreach (self::$conf["project"]["run-config"][$runConfig] as $index => $value) {
                    if(is_array($value))
                        self::$conf["project"][$index] = array_replace_recursive(self::$conf["project"][$index], $value);
                    else
                        self::$conf["project"][$index] = $value;

                }
            }

        }

    }

    public static function end()
    {
        if ((self::$refreshConfFile || self::$writeConfFile))
            file_put_contents(self::$cacheFile, serialize(self::$conf));
    }


    public static function getFileCache(\Arrow\ICacheable $object, $file, $handleParameters = array())
    {
        $cacheId = (isset($handleParameters["file_prefix"]) ? $handleParameters["file_prefix"] : md5(serialize($file)));
        $generate = false;
        $files = [];
        if (is_callable($file))
            $files = $file();
        else if (is_array($file))
            $files = $file;
        else if (is_string($file))
            $files = [$file];

        foreach ($files as $single)
            if (filemtime($single) > self::$cacheMkTime)
                $generate = true;


        if ($generate || !isset(self::$conf[$cacheId])) {
            $result = $object->generateCache($handleParameters);
            self::$conf[$cacheId] = $result;
            self::$refreshConfFile = true;
        }
        return self::$conf[$cacheId];

    }

    public static function arrayFlat($array, $prefix = '', $arrayPrefix = "__")
    {
        $result = array();

        foreach ($array as $key => $value) {
            $new_key = $prefix . (empty($prefix) ? '' : '/') . $key;

            if (is_array($value) && strpos( key(($value)), $arrayPrefix ) === false ) {
                $result = array_merge($result, self::arrayFlat($value, $new_key));
            } else {
                $result[$new_key] = $value;
            }
        }

        return $result;
    }


}