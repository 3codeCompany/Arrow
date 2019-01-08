<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 27.12.18
 * Time: 15:27
 */

namespace Arrow\TasksScheduler\Models;


use Arrow\ORM\ORM_Arrow_TasksScheduler_Models_TaskSchedulerLog;
use Arrow\ORM\Persistent\Criteria;

class TaskSchedulerLog extends ORM_Arrow_TasksScheduler_Models_TaskSchedulerLog
{

    public static function getLastOpenedFor(TaskScheduleConfig $task)
    {
        return TaskSchedulerLog::get()
            ->_scheduleConfigId($task->_id())
            ->_finished([null, "0000-00-00 00:00:00"], Criteria::C_IN)
            ->order("id", "desc")
            ->findFirst();
    }

    public static function getLastOpenedOrOpenFor(TaskScheduleConfig $task)
    {
        $log = self::getLastOpenedFor($task);

        if (!$log) {
            print "creating" . PHP_EOL . PHP_EOL;
            $log = TaskSchedulerLog::create([
                TaskSchedulerLog::F_STARTED => date("y-m-d H:i:s"),
                TaskSchedulerLog::F_SCHEDULE_CONFIG_ID => $task->_id()
            ]);
        }

        return $log;
    }
}