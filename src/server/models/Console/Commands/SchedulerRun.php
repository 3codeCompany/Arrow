<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 08.02.2018
 * Time: 23:58.
 */

namespace Arrow\Models\Commands;

use Arrow\Kernel;
use Arrow\Router;
use Arrow\TasksScheduler\Models\SchedulerRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SchedulerRun extends Command
{
    protected function configure()
    {
        $this
            ->setName('scheduler:run')
            ->setDescription('Run scheduled tasks.');
            //->addArgument('route', InputArgument::REQUIRED, 'Route list filter (strpos)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $scheduler = new SchedulerRunner();
        $scheduler->run();

    }
}
