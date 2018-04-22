<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 13.01.2018
 * Time: 15:50
 */

namespace Arrow\Utils\Controllers;

use Arrow\Kernel;
use Arrow\Models\AnnotationRouteManager;
use Arrow\Models\Project;
use Arrow\ORM\Persistent\Criteria;
use Arrow\Translations\Models\LanguageText;

use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function json_encode;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

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
        $request = Kernel::getProject()->getContainer()->get(Request::class);
        $annotatonRouteManager = new AnnotationRouteManager($request);


        $this->json([
            "ARROW_DEV_MODE" => false,
            "routes" => $annotatonRouteManager->exposeRouting(),
        ]);
    }

    /**
     * @Route("/getRoutes")
     */
    public function getRoutes()
    {
        return [];
        //$this->json(json_decode(file_get_contents(ARROW_CACHE_PATH . "/symfony/route.json")));
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


}
