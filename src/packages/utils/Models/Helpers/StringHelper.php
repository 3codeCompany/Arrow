<?php

namespace Arrow\Utils\Models\Helpers;

use function strtolower;

class StringHelper
{

    public static function toRewrite($str)
    {

        //$str = iconv("UTF-8", "ASCII//IGNORE//TRANSLIT", trim($str));
        //$str = self::removeAccents($str);
        //exit($str);
        $str = trim($str);
        $str = self::removeAccents($str);
        $str = preg_replace("/[^A-Za-z0-9?! ]/", "", $str);

        $str = strtolower($str);
        $str = str_replace(" ", "_", $str);

        return $str;
    }

    public static function removeAccents($str)
    {
        $orginal = array("Ą", "ą", "Ć", "ć", "ś", "Ś", "ń", "Ń", "ż", "Ż", "Ł", "ł", "Ź", "ź", "Ó", "ó", "Ę", "ę");
        $replace = array("A", "a", "C", "c", "s", "S", "n", "N", "z", "Ż", "L", "l", "Ź", "z", "Ó", "o", "E", "e");
        $str = str_replace($orginal, $replace, $str);
        return $str;
    }

    public static function toValidFilesystemName($str, $isFile = true)
    {
        $orginal = array("Ą", "ą", "Ć", "ć", "ś", "Ś", "ń", "Ń", "ż", "Ż", "Ł", "ł", "Ź", "ź", "Ó", "ó", "Ę", "ę", ",", "/", "\"", "'", "\"", "`", "*", " ");
        $replace = array("a", "a", "c", "c", "s", "s", "n", "n", "z", "z", "l", "l", "z", "z", "o", "o", "e", "e", "_", "_", "_", "_", "_", "_", "_", "_");
        $str = str_replace($orginal, $replace, $str /*mb_strtolower( $str, "UTF-8" )*/);
        if ($isFile) {
            //some browsers have problems with many dots
            $tmp = explode(".", $str);
            $ext = end($tmp);
            $name = implode("_", array_slice($tmp, 0, count($tmp) - 1));

            return $name . "." . $ext;
        } else {
            return $str;
        }
    }

}

?>
