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
            ->addOption("php-command", null, InputOption::VALUE_OPTIONAL, "PHP exec command", "php")
            ->addOption("task-id", null, InputOption::VALUE_OPTIONAL, "Id of task to force execute")
            ->addOption("is-subprocess", "s", InputOption::VALUE_NONE, "Flag of subprocess (only if task-id provided)")
            ->addOption("print-output", "p", InputOption::VALUE_NONE, "Prints output of process");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $scheduler = new SchedulerRunner();

        $scheduler->setPrintProcessOutput($input->getOption("print-output"));
        $scheduler->setPhpExecCommand($input->getOption("php-command"));
        $scheduler->setIsControledProcess($input->getOption("is-subprocess"));

        if ($input->getOption("is-subprocess") && !$input->getOption("task-id")) {
            $output->writeln("Unable to run list with subproces flag");
            return 1;
        }

        if ($input->getOption("task-id")) {
            $scheduler->run($input->getOption("task-id"), $input->getOption("task-id"));
        } else {
            $scheduler->run();
        }

        return 0;
    }
}
