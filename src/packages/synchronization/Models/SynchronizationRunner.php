<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 16.03.2018
 * Time: 09:18
 */

namespace Arrow\Synchronization\Models;


use Symfony\Component\Stopwatch\Stopwatch;
use function method_exists;
use function var_dump;

class SynchronizationRunner
{

    public $outputBufferEnabled = true;

    public function runConfig(SynchronizationAction $action)
    {

        $stopWatch = new Stopwatch();

        $stopWatch->start("run");


        $log = SynchronizationLog::create([
            SynchronizationLog::F_STARTED => date("y-m-d H:i:s"),
            SynchronizationLog::F_TYPE => $action->actionName
        ]);

        $return = "";
        $errors = "";


        $this->outputBufferEnabled && ob_start();
        try {

            foreach ($action->methods as $method) {
                $obj = new $method[0];
                if (method_exists($obj, $method[1])) {
                    if (!$this->outputBufferEnabled) {
                        print "<pre>";
                        print "Running:  $method[0]::$method[1](  " . implode(",", $method[2]) . " )\n\n";

                    }
                    $return .= $obj->{$method[1]}(...$method[2]);

                } else {
                    if ($this->outputBufferEnabled) {
                        $errors .= "Sych metod '{$method[0]}::{$method[1]}' don't exists!";
                    } else {
                        print  "Sych metod '{$method[0]}::{$method[1]}' don't exists!";
                    }
                }
            }

        } catch (\Exception $ex) {
            print $ex->getMessage();
            print $ex->getTraceAsString();
        }

        if ($this->outputBufferEnabled) {
            $errors .= ob_get_contents();
            if ($this->outputBufferEnabled) {
                ob_end_clean();
            }
        }

        $time = $stopWatch->getEvent("run");

        $log->setValues([
            SynchronizationLog::F_FINISHED => date("y-m-d H:i:s"),
            SynchronizationLog::F_TYPE => $action->actionName,
            SynchronizationLog::F_TIME => $time->getDuration(),
            SynchronizationLog::F_MEMORY => $time->getMemory(),
            SynchronizationLog::F_OUTPUT => $return,
            SynchronizationLog::F_ERRORS => $errors,
        ]);
        $log->save();

        if (!$this->outputBufferEnabled) {

            exit("\n\nend: " . round($time->getDuration() / 1000, 2) . " s");
        }

    }

}
