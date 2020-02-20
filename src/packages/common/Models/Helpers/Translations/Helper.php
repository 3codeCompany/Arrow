<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 29.11.2017
 * Time: 08:10
 */

namespace Arrow\Common\Models\Helpers\Translations;


use Arrow\Translations\Models\Translations;

class Helper
{

    private static $cache = null;

    private static function generateCache()
    {
        $contentsFile = "/srv/http/3code/esotiq.com/data/atom-translations.txt";
        if (isset($_SERVER["HTTP_HOST"]) && $_SERVER["HTTP_HOST"] == "betaeso.com") {
            $contentsFile = "http://esolocal.com/data/atom-translations.txt";
        }

        [$translationsNames, $translationsDescription] = explode("-----", file_get_contents($contentsFile));

        $product = explode(PHP_EOL, $translationsNames);
        foreach ($product as $k => $p) {
            $product[$k] = trim(preg_replace('/\s\s+/', ' ', $p));
        }

        $products = array_combine($product, $product);
        $products = Translations::translateTextArray($products);

        $properties = explode(PHP_EOL, $translationsDescription);
        foreach ($properties as $k => $p) {
            $properties[$k] = trim(preg_replace('/\s\s+/', ' ', $p));
        }

        $properties = array_combine($properties, $properties);
        $properties = Translations::translateTextArray($properties );

        self::$cache = [
            "products" => $products,
            "properties" => $properties,
        ];

    }

    public static function translateProductsName($products)
    {
        if (self::$cache == null) {
            self::generateCache();
        }

        foreach ($products as $index => $p) {
            $p["name"] = strtr($p["name"], self::$cache["products"]);
            $tmp[$index]["name"] = $p["name"];
        }

        return $tmp;

    }

    public static function translateProductListObject($object, $prefix = false)
    {
        if (self::$cache == null) {
            self::generateCache();
        }

        $setPrefix = $prefix == false ? "name" : $prefix;

        foreach ($object["data"] as $index => $p) {
            $p[$setPrefix] = strtr($p[$setPrefix], self::$cache["products"]);
            $object["data"][$index][$setPrefix] = $p[$setPrefix];
        }

        return $object;
    }

    public static function translatePropertyValue($properties)
    {
        if (self::$cache == null) {
            self::generateCache();
        }

        foreach ($properties as $index => $p) {

            $properties[$index]["PP:value"] = str_ireplace(array_keys(self::$cache["properties"]), array_values(self::$cache["properties"]), $p["PP:value"]);
            //$p->setValue("PP::value", strtr($p["PP::value"], self::$cache["products"]));
        }

        return $properties;
    }

}
