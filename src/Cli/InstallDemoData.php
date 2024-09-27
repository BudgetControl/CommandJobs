<?php

declare(strict_types=1);

namespace Budgetcontrol\jobs\Cli;

use Faker\Factory;
use Budgetcontrol\Library\Model\User;
use Budgetcontrol\jobs\Cli\JobCommand;
use Budgetcontrol\Library\Model\Model;
use Budgetcontrol\Library\Model\Payee;
use Budgetcontrol\Library\Model\Income;
use Budgetcontrol\Library\Model\Wallet;
use Budgetcontrol\Library\Model\Expense;
use Budgetcontrol\Library\Model\Workspace;
use Budgetcontrol\Library\Model\PlannedEntry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Budgetcontrol\Library\Model\WorkspaceSettings;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Budgetcontrol\Library\ValueObject\WorkspaceSetting;

class InstallDemoData extends JobCommand
{

    protected string $command = 'core:install-demo-data';

    const USER_EMAIL = 'demo@budgetcontrol.cloud';
    const USER_PASSWORD = 'BY3PIViM-4ieFGm';

    public function configure()
    {
        $this->setName($this->command)
            ->setDescription('Install demo data')
            ->setHelp("This command install demo data")
            ->addOption('currency_id', null, InputOption::VALUE_OPTIONAL, 'Currency ID', 2)
            ->addOption('payment_type_id', null, InputOption::VALUE_OPTIONAL, 'Payment Type ID', 1)
            ->addOption('entry', null, InputOption::VALUE_OPTIONAL, 'Numbers of entries to create', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $currency_id = $input->getOption('currency_id');
        $payment_type_id = $input->getOption('payment_type_id');
        $entry = $input->getOption('entry');
        $faker = Factory::create();

        $user = User::create([
            'name' => 'Demo User',
            'email' => self::USER_EMAIL,
            'password' => self::USER_PASSWORD,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString()
        ]);

        // Create workspace
        $workspace = Workspace::create([
            'name' => 'Demo Workspace',
            'description' => 'Demo Workspace',
            'currency_id' => $currency_id,
            'payment_type_id' => $payment_type_id,
            'user_id' => $user->id,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString()
        ]);

        // set relation with user
        $workspace->user_id = $user->id;
        $workspace->save();

        // Create wallet
        $wallet = Wallet::create([
            "name" => "Bank Account",
            "color" => "#e6e632ff",
            "invoice_date" => null,
            "closing_date" => null,
            "payment_account" => null,
            "type" => "bank",
            "installement_value" => null,
            "currency" => $currency_id,
            "balance" => 3000.00,
            "exclude_from_stats" => false,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            "workspace_id" => $workspace->id
        ]);

        // Create workspace settings
        $settings = WorkspaceSetting::create(['currency_id' => $currency_id, 'payment_type_id' => $payment_type_id]);
        WorkspaceSettings::create([
            'workspace_id' => $workspace->id,
            'setting' => 'app_configurations',
            'data' => $settings
        ]);

        // Create incomes
        $dateTime = new \DateTime();
        for ($i = 0; $i < $entry; $i++) {
            Expense::create([
                "amount" => rand(1, 1000),
                "note" => $faker->sentence(rand(1, 20)),
                "category_id" => rand(1, 76),
                "account_id" => $wallet->id,
                "currency_id" => $currency_id,
                "payment_type_id" => $payment_type_id,
                "date_time" => $dateTime->format('Y-m-d H:i:s'),
                "label" => [],
                "waranty" => 0,
                "confirmed" => 1,
                'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'type' => \Budgetcontrol\Library\Entity\Entry::incoming->value,
                'workspace_id' => $workspace->id,
            ]);
        }

        Income::create([
            "amount" => rand(1, 1000),
            "note" => $faker->sentence(rand(1, 20)),
            "category_id" => 73,
            "account_id" => $wallet->id,
            "currency_id" => $currency_id,
            "payment_type_id" => $payment_type_id,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 0,
            "confirmed" => 1,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'type' => \Budgetcontrol\Library\Entity\Entry::incoming->value,
            'workspace_id' => $workspace->id,
        ]);

        Income::create([
            "amount" => rand(1, 1000),
            "note" => $faker->sentence(rand(1, 20)),
            "category_id" => 74,
            "account_id" => $wallet->id,
            "currency_id" => $currency_id,
            "payment_type_id" => $payment_type_id,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 0,
            "confirmed" => 1,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'type' => \Budgetcontrol\Library\Entity\Entry::incoming->value,
            'workspace_id' => $workspace->id,
        ]);

        Income::create([
            "amount" => rand(1, 1000),
            "note" => $faker->sentence(rand(1, 20)),
            "category_id" => 74,
            "account_id" => $wallet->id,
            "currency_id" => $currency_id,
            "payment_type_id" => $payment_type_id,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 0,
            "confirmed" => 1,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'type' => \Budgetcontrol\Library\Entity\Entry::incoming->value,
            'workspace_id' => $workspace->id,
        ]);

        // Create a Model
        Model::create([
            "amount" => rand(1, 1000),
            "note" => "Model demo",
            "category_id" => 12,
            "account_id" => $wallet->id,
            "currency_id" => $currency_id,
            "payment_type_id" => $payment_type_id,
            "label" => [],
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'type' => \Budgetcontrol\Library\Entity\Entry::incoming->value,
            'workspace_id' => $workspace->id,
            'account_id' => $wallet->id,
        ]);

        Payee::create([
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'Payee 1 Demo',
            'workspace_id' => $workspace->id,
        ]);

        PlannedEntry::create([
            "amount" => rand(1, 1000),
            "note" => "test",
            "category_id" => 12,
            "type" => "daily",
            "account_id" => $wallet->id,
            "currency_id" => $currency_id,
            "payment_type_id" => $payment_type_id,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            'type' => \Budgetcontrol\Library\Entity\Entry::incoming->value,
            'workspace_id' => $workspace->id,
            'account_id' => $wallet->id,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
    ]);

        return Command::SUCCESS;
    }
}
