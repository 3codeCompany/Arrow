<?php
namespace Arrow;
use Arrow\Models\Project;

class CacheProvider extends Object
{

    private static $cacheFile = "";
    private static $conf = array();
    private static $refreshConfFile = false;
    private static $writeConfFile = true;
    private static $forceRefresh = false;


    public static function setForceRefresh($forceRefresh)
    {
        self::$forceRefresh = $forceRefresh;
    }

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
        }

    }

    public static function end()
    {
        if ((self::$refreshConfFile && self::$writeConfFile) || self::$forceRefresh)
            file_put_contents(self::$cacheFile, serialize(self::$conf));
    }


    public static function getFileCache(\Arrow\ICacheable $object, $file, $handleParameters = array())
    {

        $cacheId = (isset($handleParameters["file_prefix"]) ? $handleParameters["file_prefix"] : md5(serialize($file)));
        $generateCache = false;

        if ($generateCache || self::$forceRefresh || !isset(self::$conf[$cacheId])) {
            $result = $object->generateCache($handleParameters);
            self::$conf[$cacheId] = $result;
            self::$refreshConfFile = true;
        }

        return self::$conf[$cacheId];

    }

    public static function getCache($sourceFile, $cacheHandle, $handleParameters = array())
    {
        exit($sourceFile);
    }


    public static function array2string($array, $indent = 0)
    {
        //todo zabezpieczyc eskejpowanie
        $ret_str = "";
        $first = true;

        foreach ($array as $key => $value) {
            if (!$first)
                $ret_str .= ", \n";
            else
                $first = false;

            $ret_str .= str_repeat(" ", $indent);
            $key = (is_int($key)) ? $key : "'{$key}'";

            if (is_array($value)) {
                $ret_str .= "$key => array(\n" . self::array2string($value, $indent + 5) . "\n" . str_repeat(" ", $indent) . ")";
            } elseif (is_string($value)) {
                $ret_str .= "$key => '$value'";
            } elseif (is_int($value)) {
                $ret_str .= "$key => $value";
            } elseif (is_bool($value)) {
                $ret_str .= "$key => " . ($value ? "true" : "false");
            }
        }
        return $ret_str;
    }

    public static function array2File($array, $name)
    {
        $file_str = "<?php\n $" . "$name = array(\n";
        $file_str .= self::array2string($array, 6);
        $file_str .= "\n );\n?>";
        return $file_str;
    }
}