<?php

declare(strict_types=1);

namespace Budgetcontrol\jobs\Cli;

use Budgetcontrol\jobs\Cli\JobCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;

class InstallDemoData extends JobCommand
{

    protected string $command = 'core:install-demo-data';

    const USER_EMAIL = 'demo@budgetcontrol.cloud';
    const USER_PASSWORD = 'BY3PIViM-4ieFGm';

    public function configure()
    {
        $this->setName($this->command)
            ->setDescription('Clear db')
            ->setHelp("This command will remove all data from the database");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tables = [
            'budgets',
            'categories',
            'currencies',
            'entries',
            'entry_labels',
            'failed_jobs',
            'labels',
            'migrations',
            'model_labels',
            'models',
            'ms_migrations',
            'password_reset_tokens',
            'payees',
            'payments_types',
            'personal_access_tokens',
            'planned_entries',
            'planned_entry_labels',
            'sub_categories',
            'users',
            'user_settings',
            'wallets',
            'workspaces',
            'workspace_settings',
            'workspaces_users_mm'
        ];

        foreach ($tables as $table) {
            $query = "TRUNCATE TABLE $table";
            Db::statement($query);
        }

        return Command::SUCCESS;
    }
}
