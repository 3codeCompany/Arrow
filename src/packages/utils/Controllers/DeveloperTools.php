<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 13.01.2018
 * Time: 15:50
 */

namespace Arrow\Utils\Controllers;

use Arrow\ORM\Persistent\Criteria;
use Arrow\Translations\Models\LanguageText;
use const ARROW_CACHE_PATH;
use const ARROW_DEV_MODE;
use const ARROW_DOCUMENTS_ROOT;
use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function json_encode;
use const JSON_PRETTY_PRINT;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Routing\Annotation\Route;
use function var_dump;


/**
 * Class DeveloperTools
 * @package Arrow\Utils\Controllers
 * @Route("/developer")
 */
class DeveloperTools extends \Arrow\Models\Controller
{


    /**
     * @Route("/index")
     *
     */
    public function index()
    {
        $this->json([
            "ARROW_DEV_MODE" => false,
            "routes" => json_decode(file_get_contents(ARROW_CACHE_PATH . "/symfony/route.json"))
        ]);
    }

    /**
     * @Route("/getRoutes")
     */
    public function getRoutes()
    {
        $this->json(json_decode(file_get_contents(ARROW_CACHE_PATH . "/symfony/route.json")));
    }

    /**
     * @Route("/cache/remove")
     */
    public function removeCache()
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(ARROW_CACHE_PATH, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileInfo) {
            if (!$fileInfo->isDir()) {
                unlink($fileInfo->getRealPath());
            }
        }
        $this->json([]);
    }

    /**
     * @Route("/changeDevState")
     */
    public function changeDevState()
    {
        $_SESSION["ARROW_DEV_MODE"] = !ARROW_DEV_MODE;
        $this->json([$_SESSION["ARROW_DEV_MODE"]]);
    }

    /**
     * @Route("/dumpLangFiles")
     */
    public function dumpLangFiles()
    {
        $lang = "en";
        $text = LanguageText::get()
            ->_lang($lang)
            ->_value("", Criteria::C_NOT_EQUAL)
            ->findAsFieldArray(LanguageText::F_VALUE, LanguageText::F_ORIGINAL);

        $text["language"] = "en";

        file_put_contents(ARROW_DOCUMENTS_ROOT . "/build/js/lang/{$lang}.json", json_encode($text, JSON_PRETTY_PRINT));

        return [];
    }


}
