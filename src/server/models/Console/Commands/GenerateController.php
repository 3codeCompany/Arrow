<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 08.02.2018
 * Time: 23:58
 */

namespace Arrow\Models\Commands;


use Arrow\Models\Commands\Generators\CRUDControllerGenerator;
use Arrow\Router;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Debug\Debug;

class GenerateController extends Command
{
    protected function configure()
    {
        Debug::enable();
        $this
            ->setName('generate:controller')
            ->setDescription('Generate controller ')
            ->addArgument("name", InputArgument::OPTIONAL, "Name of controller")
            ->addArgument("model", InputArgument::OPTIONAL, "Name of controled model");
        //->addArgument("Class", InputArgument::REQUIRED, "Name of controller");;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $controller = $input->getArgument('name');
        $model = $input->getArgument('model');

        if(!class_exists($model)){
            $output->writeln("Model not found");
            return;
        }

        $prefix = "";

        //ask in interactive mode
        if (empty($controller)) {
            $helper = $this->getHelper('question');
            $question = new Question('Please enter the name of the controller: App\\Controllers\\');
            $result = $helper->ask($input, $output, $question);

        }

        //name convenction error
        if (substr($controller, -10) != "Controller") {
            $output->writeln("Bad naming convenction (Controller postfix required)");
            return;
        }


        $path = ARROW_APPLICATION_PATH . "/Controllers/" . str_replace("\\", DIRECTORY_SEPARATOR, $controller) . ".php";

        //adding common prefix
        $suffix = "";
        $controlerBaseNamespace = "App\\Controllers\\";
        if (strpos($controller, $controlerBaseNamespace) !== 0) {
            $controller = $controlerBaseNamespace . $controller;
        }


        if (!file_exists($path) || true) {
            $dir = dirname($path);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $generator = new CRUDControllerGenerator($controller, $model, $controlerBaseNamespace);
            $controlerContent = $generator->generate();

            file_put_contents($path, "<?\n{$controlerContent}\n//" . time());
        } else {
            $output->writeln("File already exists");
            return;
        }


        $output->writeln("The bundle is: " . $controller);

    }


}
