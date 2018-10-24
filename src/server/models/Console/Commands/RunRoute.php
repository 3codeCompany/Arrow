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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunRoute extends Command
{
    protected function configure()
    {
        $this
            ->setName('run:route')
            ->setDescription('Run speified route.')
            ->addArgument('route', InputArgument::REQUIRED, 'Route list filter (strpos)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getArgument('route')) {
            /** @var Router $router */
            $router = Kernel::getProject()->getContainer()->get(Router::class);
            $output->writeln("Running {$input->getArgument('route')}");

            $router->execute($input->getArgument('route'));

            $output->writeln('Finished.');
        }
    }
}
