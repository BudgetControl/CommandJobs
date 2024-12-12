<?php

declare(strict_types=1);

namespace Budgetcontrol\jobs\Cli;

use Budgetcontrol\jobs\Cli\JobCommand;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearDatabase extends JobCommand
{

    protected string $command = 'core:clear';

    const USER_EMAIL = 'demo@budgetcontrol.cloud';
    const USER_PASSWORD = 'BY3PIViM-4ieFGm';

    public function configure()
    {
        $this->setName($this->command)
            ->setDescription('Clear db')
            ->addArgument('action', InputArgument::OPTIONAL, 'Action to perform', 'clear')
            ->setHelp("This command will remove all data from the database");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Clear db');
        $tables = [
            'budgets',
            'entry_labels',
            'entries',
            'sub_categories',
            'categories',
            'currencies',
            'failed_jobs',
            'labels',
            'model_labels',
            'models',
            'ms_migrations',
            'payees',
            'payments_types',
            'planned_entries',
            'planned_entry_labels',
            'wallets',
            'workspace_settings',
            'workspaces_users_mm',
            'workspaces',
            'users',
        ];

        if ($input->getArgument('action') == 'drop') {
            $command = 'DROP table';
        } else {
            $command = 'DELETE FROM';
        }


        foreach ($tables as $table) {
            $output->writeln($command . ': ' . $table);
            $query = "$command $table";
            Db::statement($query);
        }

        if ($input->getArgument('action') == 'drop') {
            $command = 'DROP table';
            $enumTypes = [
                'entry',
                'planning',
                'wallet'
            ];

            foreach ($enumTypes as $type) {
                $output->writeln('DROP TYPE: ' . $type);
                $query = "DROP TYPE IF EXISTS $type";
                Db::statement($query);
            }
        }

        $output->writeln('Database cleared');
        return Command::SUCCESS;
    }
}
