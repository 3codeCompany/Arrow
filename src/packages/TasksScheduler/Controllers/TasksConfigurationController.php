<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 20.12.18
 * Time: 11:08
 */

namespace Arrow\TasksScheduler\Controllers;

use Arrow\Common\Models\Helpers\TableListORMHelper;
use Arrow\Common\Models\Helpers\Validator;
use Arrow\TasksScheduler\Models\SchedulerRunner;
use Arrow\TasksScheduler\Models\TaskScheduleConfig;
use Arrow\TasksScheduler\Models\TaskSchedulerLog;
use Cron\CronExpression;
use Symfony\Component\HttpFoundation\Request;
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

        $ret = $helper->getListData(TaskScheduleConfig::get());

        foreach ($ret["data"] as &$row) {
            $dates = CronExpression::factory($row[TaskScheduleConfig::F_CRON_EXPRESSION])
                ->getMultipleRunDates(3);

            $row["runDates"] = [];
            foreach ($dates as $runDate) {
                $row["runDates"][] = $runDate->format("Y-m-d H:i:s");
            }

        }

        return $ret;
    }

    /**
     * @Route("/add")
     */
    public function add(Request $request)
    {

        $data = $request->get("data");
        unset($data["runDates"]);
        $v = Validator::create($data)
            ->required([TaskScheduleConfig::F_NAME, TaskScheduleConfig::F_TASK, TaskScheduleConfig::F_MAX_EXECUTE_TIME]);

        $cron = implode(" ", $data["schedule_config"]);
        unset($data["schedule_config"]);


        if (!CronExpression::isValidExpression($cron)) {
             $v->addFormError(TaskScheduleConfig::F_SHELUDE_CONFIG, "Nieprawidłowy format cron");
        }

        $tmp = explode("::", $data[TaskScheduleConfig::F_TASK]);
        if (count($tmp) == 2) {
            if (!class_exists($tmp[0]) || !method_exists($tmp[0], $tmp[1])) {
                $v->addFieldError(TaskScheduleConfig::F_TASK, "Nie znaleziono możliwości uruchomienia zadania " . $data[TaskScheduleConfig::F_TASK]);
            }
        } else {
            $v->addFieldError(TaskScheduleConfig::F_TASK, "Nie znaleziono możliwości uruchomienia zadania ");
        }


        if (!$v->check()) {
            $this->json($v->response());
        }

        $data[TaskScheduleConfig::F_CRON_EXPRESSION] = $cron;


        if (isset($data["id"])) {
            $obj = TaskScheduleConfig::get()->findByKey($data["id"]);
            $obj->setValues($data);
            $obj->save();

        } else {
            TaskScheduleConfig::create($data);
        }
        return [];
    }

    /**
     * @Route("/run/{key}")
     */
    public function run($key)
    {
        $obj = TaskScheduleConfig::get()->findByKey($key);

        $runner = new SchedulerRunner();
        $log = $runner->runTask($obj);

        return ["log" => $log];
    }


    /**
     * @Route("/list-log-data")
     */
    public function listLogData()
    {

        $helper = new TableListORMHelper();
        $helper->addDefaultOrder("id", "desc");

        $crit = TaskSchedulerLog::get()
            ->_join(TaskScheduleConfig::class, [TaskSchedulerLog::F_SCHEDULE_CONFIG_ID => "id"], "C", [TaskScheduleConfig::F_NAME]);

        return $helper->getListData($crit);

    }

    /**
     * @Route("/cron-schedule-info")
     */
    public function cronScheduleInfo(Request $request)
    {

        $data = $request->get("data");

        $expression = implode(" ", $data);

        try {
            $dates = CronExpression::factory($expression)
                ->getMultipleRunDates(10);

            $ret = [];
            foreach ($dates as $runDate) {
                $ret[] = $runDate->format("Y-m-d H:i");
            }

            return $ret;
        } catch (\Exception $ex) {
            return ["error" => "Not valid expression: '{$expression}'"];
        }

        return $request->get("data");

    }

}