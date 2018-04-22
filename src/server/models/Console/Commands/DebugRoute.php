<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 08.02.2018
 * Time: 23:58
 */

namespace Arrow\Models\Commands;


use Arrow\Kernel;

use Arrow\Models\AnnotationRouteManager;
use Arrow\Router;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

class DebugRoute extends Command
{
    protected function configure()
    {
        $this
            ->setName('debug:router')
            ->setDescription('List of routing paths.')
            ->addArgument("filter", InputArgument::OPTIONAL, "Route list filter (strpos)")
            ->addArgument("json", InputArgument::OPTIONAL, "Route list filter (strpos)")
            ->addOption("json");

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if ($input->getOption("json")) {
            $request = Kernel::getProject()->getContainer()->get(Request::class);
            $annotatonRouteManager = new AnnotationRouteManager($request);
            $output->write(json_encode($annotatonRouteManager->exposeRouting()));
        } else {

            $routeColection = Kernel::getProject()->getContainer()->get(Router::class)
                ->getSymfonyRouter()
                ->getRouteCollection();
            $filter = $input->getArgument('filter');

            $find = false;
            /** @var \Symfony\Component\Routing\Route $el */
            foreach ($routeColection as $el) {
                $path = $el->getPath();
                if (!$filter || strpos($path, $filter) !== false) {
                    $defaults = $el->getDefaults();
                    $row = [
                        str_pad($defaults["_package"], 15, " ", STR_PAD_RIGHT),

                        str_pad($el->getPath(), 55, " ", STR_PAD_RIGHT),
                        str_pad($defaults["_controller"], 15, " ", STR_PAD_RIGHT),
                    ];
                    $output->writeln("| " . implode(" | ", $row));
                    //$output->writeln(print_r(, 1));
                    $find = true;
                }
            }
            if (!$find) {
                $output->writeln("No matching routes found for: " . ($filter ? $filter : "*"));
            }
        }
    }
}
