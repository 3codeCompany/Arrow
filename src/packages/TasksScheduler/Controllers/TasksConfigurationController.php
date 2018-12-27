<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 20.12.18
 * Time: 11:08
 */

namespace Arrow\TasksScheduler\Controllers;

use Arrow\Common\Models\Helpers\TableListORMHelper;
use Arrow\TasksScheduler\Models\TaskScheduleConfig;
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
    public function list()
    {
        return [];
    }

    /**
     * @Route("/list-data")
     */
    public function listData()
    {

        $helper = new TableListORMHelper();

        return $helper->getListData(TaskScheduleConfig::get());
    }

}