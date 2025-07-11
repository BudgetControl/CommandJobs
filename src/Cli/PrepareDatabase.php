<?php

declare(strict_types=1);

namespace Budgetcontrol\jobs\Cli;

use Budgetcontrol\jobs\Cli\JobCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Budgetcontrol\Seeds\Resources\Seed;
use Illuminate\Support\Facades\Log;

class PrepareDatabase extends JobCommand
{

    protected string $command = 'core:install';

    public function configure()
    {
        $this->setName($this->command)
            ->setDescription('Install db')
            ->setHelp("This command will install the base data for the application");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //execute seeders
        $output->writeln('Install db');
        $this->output = $output;
        Log::info('Install db');
        shell_exec('vendor/bin/phinx migrate');

        Log::info('Seed db');
        $output->writeln('Seed db');
        $seeders = new Seed();
        $seeders->runAllSeeds();

        $output->writeln('Database has been prepared');
        $this->invokeClearCache('*');
        return Command::SUCCESS;
    }
}
