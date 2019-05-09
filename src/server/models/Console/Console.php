<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 08.02.2018
 * Time: 23:56
 */

namespace Arrow\Models;


use Arrow\Models\Commands\ClearCache;
use Arrow\Models\Commands\DebugRoute;
use Arrow\Models\Commands\GenerateController;
use Arrow\Models\Commands\RunRoute;
use Arrow\Models\Commands\SchedulerRun;
use Symfony\Component\Console\Application;

class Console
{
    public function init()
    {

        \Arrow\Kernel::init();

        \Arrow\Router::getDefault(\Arrow\Kernel::$project->getContainer());
        \Symfony\Component\Debug\Debug::enable();
        $application = new Application();
        $application->add(new DebugRoute());
        $application->add(new ClearCache());
        $application->add(new GenerateController());
        $application->add(new SchedulerRun());
        $application->add(new RunRoute());

        $application->run();
    }
}