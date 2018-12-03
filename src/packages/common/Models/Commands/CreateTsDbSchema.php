<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 08.02.2018
 * Time: 23:58
 */

namespace Arrow\Common\Models\Commands;


use App\Models\DB\TypeScriptSchema;
use Arrow\ORM\Schema\SchemaReader;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateTsDbSchema extends Command
{
    protected function configure()
    {
        $this
            ->setName('db:ts-schema-gen')
            ->setDescription('Generates schema for typescript.');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $transformer = new TypeScriptSchema(ARROW_PROJECT . "/build/js/db");

        $schema = (new SchemaReader())->readSchemaFromFile(ARROW_PROJECT . "/app/conf/db-schema.xml");

        $transformer->transform($schema);



        /*$files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(ARROW_CACHE_PATH, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileInfo) {
            if (!$fileInfo->isDir()) {
                unlink($fileInfo->getRealPath());
            }
        }*/

        $output->writeln("Schema generated");

    }
}