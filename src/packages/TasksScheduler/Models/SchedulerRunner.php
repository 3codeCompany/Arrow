<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 28.12.18
 * Time: 13:44
 */

namespace Arrow\TasksScheduler\Models;


use Cron\CronExpression;
use Crunz\Schedule;
use Symfony\Component\Stopwatch\Stopwatch;

class SchedulerRunner
{

    /**
     * @var Schedule
     */
    private $schedule;

    public function run()
    {
        set_time_limit(3600);

        
        $this->schedule = new Schedule();

        $tasks = TaskScheduleConfig::get()
            ->_active(1)
            ->find();


        /** @var TaskScheduleConfig $task */
        foreach ($tasks as $task) {
            $this->runTaskInTime($task);
        }
    }


    private function runTaskInTime(TaskScheduleConfig $task)
    {

        if (CronExpression::factory($task->_sheludeConfig())->isDue("now")) {
            $this->runTask($task);
        }

    }

    public function runTask(TaskScheduleConfig $task): TaskSchedulerLog
    {


        $stopWatch = new Stopwatch();

        $stopWatch->start("run");


        $return = "";
        $errors = "";


        $log = TaskSchedulerLog::create([
            TaskSchedulerLog::F_STARTED => date("y-m-d H:i:s"),
            TaskSchedulerLog::F_SCHEDULE_CONFIG_ID => $task->_id()
        ]);

        ob_start();

        try {

            $tmp = explode("::", $task[TaskScheduleConfig::F_TASK]);
            $obj = new $tmp[0];

            if (method_exists($obj, $tmp[1])) {
                $return = $obj->{$tmp[1]}();
                if (!is_string($return)) {
                    $return = print_r($return, 1);
                }
            } else {
                $errors = "Sych metod '{$tmp[0]}::{$tmp[1]}' don't exists!";
            }


        } catch (\Exception $ex) {
            print $ex->getMessage();
            print $ex->getTraceAsString();
        }

        $errors .= ob_get_contents();

        ob_end_clean();


        $time = $stopWatch->getEvent("run");

        $log->setValues([
            TaskSchedulerLog::F_FINISHED => date("y-m-d H:i:s"),
            TaskSchedulerLog::F_TIME => $time->getDuration(),
            TaskSchedulerLog::F_MEMORY => $time->getMemory(),
            TaskSchedulerLog::F_OUTPUT => $return,
            TaskSchedulerLog::F_ERRORS => $errors,
        ]);
        $log->save();
        $task->_lastRun(date("y-m-d H:i:s"));
        $task->save();
        return $log;

    }


}


