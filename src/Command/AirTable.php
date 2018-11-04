<?php

namespace Joshua19\StaticMapSaver\Command;

use Joshua19\StaticMapSaver\MapSaver;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AirTable extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('airtable')
            ->setAliases(['air'])
            ->setDescription('Get Airtable data')
            // @see https://symfony.com/doc/current/console/input.html
            ->addOption(
                'table',
                't',
                InputOption::VALUE_OPTIONAL,
                'The Airtable Table name to which to get data',
                'Places'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Limit how many results to return',
                1000
            )
            // @TODO make this work
            ->addOption(
                'offset',
                'o',
                InputOption::VALUE_OPTIONAL,
                'The offset start at 0',
                0
            )
            ->addOption(
                'flush',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Flush Cache 1/0',
                getenv('AIRTABLE_API_CACHE_DATA')
            )
            ->addOption(
                'dir',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Optionally set the directory path in which your .env file exists, ex: /var/www/',
                DEFAULT_ENV_DIRECTORY
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = $input->getOption('dir');
        $table = $input->getOption('table');
        $limit = $input->getOption('limit');
        $offset = $input->getOption('offset');
        $flush = $input->getOption('flush');


        $output->writeln('### ENV directory '.$dir.' ###');

        $mapSaver = new MapSaver($dir);

        $mapSaver
            ->setUseAirTableCache($flush)
            ->setLimit($limit)
            ->getAirTableList($table, $offset, true);

        $output->writeln($this->getRunStats());
    }
}
