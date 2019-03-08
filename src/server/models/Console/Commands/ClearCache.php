<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 08.02.2018
 * Time: 23:58
 */

namespace Arrow\Models\Commands;


use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCache extends Command
{
    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Clears cache.');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(ARROW_CACHE_PATH, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileInfo) {
            if (!$fileInfo->isDir() && $fileInfo->getFilename() != ".gitignore") {
                unlink($fileInfo->getRealPath());
            }
        }

        $output->writeln("Cache is clear");

    }
}