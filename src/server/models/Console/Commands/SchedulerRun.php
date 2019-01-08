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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SchedulerRun extends Command
{
    protected function configure()
    {
        $this
            ->setName('scheduler:run')
            ->setDescription('Run scheduled tasks.')
            ->addOption("php-command", null, InputOption::VALUE_OPTIONAL, "PHP exec command", "php");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $scheduler = new SchedulerRunner();
        $scheduler->setPhpExecCommand($input->getOption("php-command"));
        $scheduler->run();

    }
}
