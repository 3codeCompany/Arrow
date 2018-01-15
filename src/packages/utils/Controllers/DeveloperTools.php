<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 13.01.2018
 * Time: 15:50
 */

namespace Arrow\Utils\Controllers;

use const ARROW_CACHE_PATH;
use const ARROW_DEV_MODE;
use function file_get_contents;
use function json_decode;
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
            "ARROW_DEV_MODE" => ARROW_DEV_MODE,
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


}