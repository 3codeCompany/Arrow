<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 28.12.18
 * Time: 13:44
 */

namespace Arrow\TasksScheduler\Models;


use Arrow\Kernel;
use Arrow\ORM\Persistent\Criteria;
use Cron\CronExpression;
use Crunz\Schedule;
use Symfony\Component\Process\Process;
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


        $active = [];

        /** @var TaskScheduleConfig $task */
        foreach ($tasks as $task) {
            $active[] = $this->runTaskInTime($task);
        }

        while (count($active) > 0) {
            foreach ($active as $key => $el) {
                if ($el instanceof Process) {
                    if (!$el->isRunning()) {
                        unset($active[$key]);
                    }
                } else {
                    unset($active[$key]);
                }
            }
            sleep(0.2);
        }
    }


    private function runTaskInTime(TaskScheduleConfig $task)
    {
        if (CronExpression::factory($task->_cronExpression())->isDue("now")) {
            if (!Kernel::isInCLIMode()) {
                return $this->runTask($task);
            } else {
                return $this->runFromConsole($task);
            }
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
            TaskSchedulerLog::F_ERRORS => $log->_errors() ? $log->_errors() . PHP_EOL . $errors : $errors,
        ]);
        $log->save();
        $task->_lastRun(date("y-m-d H:i:s"));
        $task->save();
        return $log;

    }

    public function runFromConsole(TaskScheduleConfig $task): Process
    {

        $log = TaskSchedulerLog::get()
            ->_scheduleConfigId($task->_id())
            ->_finished([null, "0000-00-00 00:00:00"], Criteria::C_IN)
            ->findFirst();

        if ($log) {
            $date = new \DateTime($log->_started());

            if ($date->getTimestamp() < time() - $task->_maxExecuteTime()) {
                $error = "Job older than  {$task->_maxExecuteTime()}s. Automatic finished";
                $log->setValues([
                    TaskSchedulerLog::F_FINISHED => date("Y-m-d H:i:s"),
                    TaskSchedulerLog::F_TIME => $task->_maxExecuteTime() * 1000,
                    TaskSchedulerLog::F_ERRORS => $log->_errors() ? $log->_errors() . PHP_EOL . $error : $error,
                ]);
                $log->save();
            } else {
                $error = "Job still running. Aborting new task";
                $log->setValues([
                    ///TaskSchedulerLog::F_FINISHED => date("y-m-d H:i:s"),
                    TaskSchedulerLog::F_ERRORS => $log->_errors() ? $log->_errors() . PHP_EOL . $error : $error,
                ]);
                $log->save();
                return null;
            }
        }


        $process = new Process(["php",
            "bin/console",
            "run:route",
            "-w",
            "/tasksscheduler/tasks-configuration/run/" . $task->_id()
        ]);
        $process->setWorkingDirectory(ARROW_PROJECT);

        $process->start(function ($type, $buffer) use ($task) {
            if (Process::ERR === $type) {
                $log = TaskSchedulerLog::get()
                    ->_scheduleConfigId($task->_id())
                    ->order("id", "desc")
                    ->_finished([null, "0000-00-00 00:00:00"], Criteria::C_IN)
                    ->findFirst();

                if (!$log) {
                    $log = TaskSchedulerLog::create([
                        TaskSchedulerLog::F_STARTED => date("y-m-d H:i:s"),
                        TaskSchedulerLog::F_SCHEDULE_CONFIG_ID => $task->_id()
                    ]);
                }

                $log->setValues([
                    TaskSchedulerLog::F_FINISHED => date("y-m-d H:i:s"),
                    TaskSchedulerLog::F_ERRORS => $log->_errors() ? $log->_errors() . PHP_EOL . $buffer : $buffer,
                ]);
                $log->save();


            } else {

            }
        });


        $log = TaskSchedulerLog::get()
            ->_scheduleConfigId($task->_id())
            ->_finished([null, "0000-00-00 00:00:00"], Criteria::C_IN)
            ->order("id", "desc")
            ->findFirst();

        $pid = $process->getPid();
        if ($log) {
            $log->_pid($pid);
            $log->save();
        }

        return $process;
    }


}



