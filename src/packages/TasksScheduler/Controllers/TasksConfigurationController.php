<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 20.12.18
 * Time: 11:08
 */

namespace Arrow\TasksScheduler\Controllers;

use Symfony\Component\Routing\Annotation\Route;


/**
 * Class TasksConfigurationController
 * @Route("/tasks-configuration")
 */

class TasksConfigurationController extends \Arrow\Models\Controller
{

    /**
     * @Route("/list")
     */
    public function list(){
        exit("test");
    }

}