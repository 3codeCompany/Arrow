<?php
/**
 * Created by PhpStorm.
 * User: artur.kmera
 * Date: 22.06.2018
 * Time: 15:12
 */

namespace Arrow\Common\Controllers;

use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DictionaryController
 * @package Arrow\Common\Controllers
 * @Route("/dictionary")
 */
class DictionaryController
{

    /**
     * @Route("/list")
     */
    public function index()
    {

        return [];
    }

}