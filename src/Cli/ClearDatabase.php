<?php

declare(strict_types=1);

namespace Budgetcontrol\jobs\Cli;

use Budgetcontrol\jobs\Cli\JobCommand;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Log;
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

    private const ENUMS = [
        'entry',
        'planning',
        'wallet',
        'status'
    ];

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
        $this->output = $output;
        $tables = [
            'budgets',
            'entry_labels',
            'entries',
            'sub_categories',
            'categories',
            'currencies',
            'failed_jobs',
            'labels',
            'goals',
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
            $tables[] = 'migrations';
            $tables[] = 'ms_migrations';
            $command = 'DROP table';
        } else {
            $command = 'DELETE FROM';
        }


        foreach ($tables as $table) {
            $output->writeln($command . ': ' . $table);
            $query = "$command $table";

            try {
                Db::statement($query);
            }catch (\Throwable $e) {
                Log::warning('Error executing query: ' . $query);
                $output->writeln('Warning: ' . $e->getMessage());
            }
        }

        if ($input->getArgument('action') == 'drop') {
            $command = 'DROP table';
            $enumTypes = self::ENUMS;

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
