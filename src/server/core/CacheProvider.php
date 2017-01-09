<?php
namespace Arrow;
use Arrow\Models\Project;

class CacheProvider extends Object
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

    public static function setRemoveWhiteSpace($removeWhiteSpace)
    {
        self::$removeWhiteSpace = $removeWhiteSpace;
    }

    public static function init()
    {
        self::$cacheFile = ARROW_CACHE_PATH . "/cached_conf.php";

        if (file_exists(self::$cacheFile)) {
            self::$conf = unserialize(file_get_contents(self::$cacheFile));
            self::$cacheMkTime = filemtime(self::$cacheFile);
        }

    }

    public static function end()
    {
        if ((self::$refreshConfFile || self::$writeConfFile) )
            file_put_contents(self::$cacheFile, serialize(self::$conf));
    }


    public static function getFileCache(\Arrow\ICacheable $object, $file, $handleParameters = array())
    {
        $cacheId = (isset($handleParameters["file_prefix"]) ? $handleParameters["file_prefix"] : md5(serialize($file)));
        $generate = false;
        $files = [];
        if(is_callable($file))
            $files = $file();
        else if(is_array($file))
            $files = $file;
        else if(is_string($file))
            $files = [$file];

        foreach($files as $single)
            if(filemtime($single)> self::$cacheMkTime)
                $generate = true;


        if ($generate || !isset(self::$conf[$cacheId])) {
            $result = $object->generateCache($handleParameters);
            self::$conf[$cacheId] = $result;
            self::$refreshConfFile = true;
        }
        return self::$conf[$cacheId];

    }


}