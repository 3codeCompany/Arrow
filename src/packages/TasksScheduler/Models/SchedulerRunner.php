<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 28.12.18
 * Time: 13:44
 */

namespace Arrow\TasksScheduler\Models;


use Arrow\Exception;
use Arrow\Kernel;
use Arrow\Models\DB;
use Arrow\ORM\Persistent\Criteria;
use Cron\CronExpression;
use Crunz\Schedule;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Symfony\Component\Stopwatch\Stopwatch;

class SchedulerRunner
{

    private $phpExecCommand = "php";
    private $printProcessOutput = false;

    /**
     * @param string $phpExecCommand
     */
    public function setPhpExecCommand(string $phpExecCommand): void
    {
        $this->phpExecCommand = $phpExecCommand;
    }

    /**
     * @param bool $printProcessOutput
     */
    public function setPrintProcessOutput(bool $printProcessOutput): void
    {
        $this->printProcessOutput = $printProcessOutput;
    }





    /**
     * @var Schedule
     */
    private $schedule;

    public function run($forceTaskId = false)
    {
        set_time_limit(5000);

        //Kernel::$project->getContainer()->get(DB::class)->exec("truncate table " . TaskSchedulerLog::getTable());


        $this->schedule = new Schedule();


        $active = [];

        if ($forceTaskId) {
            $task = TaskScheduleConfig::get()->findByKey($forceTaskId);
            if (!$task) {
                throw new Exception("Task `{$forceTaskId}` not found");
            }else{
                $active[] = $this->runTaskInEnv($task);
            }
        } else {
            $tasks = TaskScheduleConfig::get()
                ->_active(1)
                ->find();

            /** @var TaskScheduleConfig $task */
            foreach ($tasks as $task) {
                $active[] = $this->runTaskInTime($task);
            }
        }

        while (count($active) > 0) {
            foreach ($active as $key => $el) {
                if ($el instanceof Process) {
                    if (!$el->isRunning()) {
                        unset($active[$key]);
                    } else {
                        try {
                            print $el->checkTimeout() . PHP_EOL;
                        } catch (ProcessTimedOutException $ex) {
                            $log = TaskSchedulerLog::getLastOpenedFor($task);
                            $error = $ex->getMessage();
                            $log->setValues([
                                TaskSchedulerLog::F_FINISHED => date("y-m-d H:i:s"),
                                TaskSchedulerLog::F_ERRORS => $log->_errors() ? $log->_errors() . PHP_EOL . $error : $error,
                            ]);
                            $log->save();
                        }
                    }
                } else {
                    unset($active[$key]);
                }
            }
            sleep(1);
        }
    }


    private function runTaskInTime(TaskScheduleConfig $task)
    {
        if (CronExpression::factory($task->_cronExpression())->isDue("now")) {
            return $this->runTaskInEnv($task);
        }
    }

    private function runTaskInEnv(TaskScheduleConfig $task)
    {
        if (!Kernel::isInCLIMode()) {
            return $this->runTask($task);
        } else {
            return $this->runFromConsole($task);
        }
    }

    public function runTask(TaskScheduleConfig $task): TaskSchedulerLog
    {


        $stopWatch = new Stopwatch();

        $stopWatch->start("run");


        $return = "";
        $errors = "";


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

        $log = TaskSchedulerLog::getLastOpenedOrOpenFor($task);

        $log->setValues([
            TaskSchedulerLog::F_FINISHED => date("y-m-d H:i:s"),
            TaskSchedulerLog::F_TIME => $time->getDuration(),
            TaskSchedulerLog::F_MEMORY => $time->getMemory(),
            TaskSchedulerLog::F_OUTPUT => $return,
            TaskSchedulerLog::F_ERRORS => $log->_errors() ? $log->_errors() . PHP_EOL . $errors : $errors,
        ]);
        $log->save();
        //reload data from DB
        $task = TaskScheduleConfig::get()->findByKey($task->_id());
        $task->_lastRun(date("y-m-d H:i:s"));
        $task->save();
        return $log;

    }

    public function runFromConsole(TaskScheduleConfig $task): ?Process
    {

        $log = TaskSchedulerLog::getLastOpenedFor($task);

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
                $error = "[" . date("Y-m-d H:i:s") . "] Job still running. PID: {$log->_pid()}. Aborting new task";
                if($this->printProcessOutput){
                    print $error.PHP_EOL;
                }
                $log->setValues([
                    ///TaskSchedulerLog::F_FINISHED => date("y-m-d H:i:s"),
                    TaskSchedulerLog::F_ERRORS => $log->_errors() ? $log->_errors() . PHP_EOL . $error : $error,
                ]);
                $log->save();

                if($log->_pid()) {
                    $check = new Process([
                        "ps",
                        "aux",
                        "|",
                        "grep",
                        "'{$log->_pid()}'",
                    ]);

                    $check->run();
                    $output = $check->getOutput();
                    if($output == ""){
                        $error = "No process {$log->_pid()} found. Closing opened task.";
                        $log->setValues([
                            TaskSchedulerLog::F_FINISHED => date("y-m-d H:i:s"),
                            TaskSchedulerLog::F_ERRORS => $log->_errors() ? $log->_errors() . PHP_EOL . $error : $error,
                        ]);
                        $log->save();
                        if($this->printProcessOutput){
                            print $error." and continue".PHP_EOL;
                        }
                    }else{
                        return null;
                    }


                }else {
                    return null;
                }
            }
        }


        $log = TaskSchedulerLog::getLastOpenedOrOpenFor($task);


        $process = new Process([
            $this->phpExecCommand,
            "bin/console",
            "run:route",
            "-w",
            "/tasksscheduler/tasks-configuration/run/" . $task->_id()
        ]);
        $process->setTimeout($task->_maxExecuteTime());

        print $process->getCommandLine();

        $process->setWorkingDirectory(ARROW_PROJECT);


        $process->start(function ($type, $buffer) use ($task, $log) {

            if($this->printProcessOutput){
                print $buffer;
            }

            if (Process::ERR === $type) {
                //$log = TaskSchedulerLog::getLastOpenedOrOpenFor($task);

                $log->setValues([
                    TaskSchedulerLog::F_FINISHED => date("y-m-d H:i:s"),
                    TaskSchedulerLog::F_ERRORS => $log->_errors() ? $log->_errors() . PHP_EOL . $buffer : $buffer,
                ]);
                $log->save();


            } else {

            }
        });


        $log->_pid($process->getPid());
        $log->save();

        return $process;
    }


}



