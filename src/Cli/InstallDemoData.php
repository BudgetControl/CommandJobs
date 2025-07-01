<?php

declare(strict_types=1);

namespace Budgetcontrol\jobs\Cli;

use Faker\Factory;
use Illuminate\Support\Facades\Log;
use Budgetcontrol\Library\Model\Goal;
use Budgetcontrol\Library\Model\User;
use Budgetcontrol\jobs\Cli\JobCommand;
use Budgetcontrol\Library\Model\Model;
use Budgetcontrol\Library\Model\Payee;
use Budgetcontrol\Library\Model\Income;
use Budgetcontrol\Library\Model\Wallet;
use Budgetcontrol\Library\Model\Expense;
use Budgetcontrol\Library\Model\Currency;
use Budgetcontrol\Library\Model\Workspace;
use Budgetcontrol\Library\Model\PlannedEntry;
use Budgetcontrol\Library\Model\Saving;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Budgetcontrol\Library\Model\WorkspaceSettings;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Budgetcontrol\Library\ValueObject\WorkspaceSetting;
use Budgetcontrol\Seeds\Resources\Seeds\Seed;

class InstallDemoData extends JobCommand
{

    protected string $command = 'core:demo-data';

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
        $output->writeln('Install demo data');
        Log::info('Install demo data');
        $this->output = $output;

        Log::info('Run basic seeds');
        $output->writeln('Run basic seeds');

        $currency_id = $input->getOption('currency_id');
        $payment_type_id = $input->getOption('payment_type_id');
        $entry = $input->getOption('entry');
        $faker = Factory::create();

        // Create user
        $output->writeln('Create user');
        Log::debug('Create user');
        $userUUID = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $user = User::create([
            'name' => 'Demo User',
            'email' => self::USER_EMAIL,
            'password' => self::USER_PASSWORD,
            'uuid' => $userUUID
        ]);

        // Create workspace
        $output->writeln('Create workspace');
        Log::debug('Create workspace');
        $wsUUID = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $workspace = Workspace::create([
            'name' => 'Demo Workspace',
            'description' => 'Demo Workspace',
            'user_id' => $user->id,
            'uuid' => $wsUUID
        ]);

        // set relation with user
        $workspace->user_id = $user->id;
        $workspace->save();

        // set relation with user and workspace
        $user = \Budgetcontrol\Library\Model\User::where('uuid', $userUUID)->first();
        $workspace = \Budgetcontrol\Library\Model\Workspace::where('uuid', $wsUUID)->first();

        $workspace->users()->attach($user);
        $workspace->save();

        // Create wallet
        $output->writeln('Create wallet');
        Log::debug('Create wallet');
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
        $output->writeln('Create workspace settings');
        Log::debug('Create workspace settings');

        $currency = Currency::find($currency_id);
        $settings = WorkspaceSetting::create($currency->toArray(), $payment_type_id);
        WorkspaceSettings::create([
            'workspace_id' => $workspace->id,
            'data' => $settings
        ]);

        // Create incomes
        $dateTime = new \DateTime();
        $output->writeln('Create ' . $entry . ' expenses');
        Log::debug('Create ' . $entry . ' expenses');
        for ($i = 0; $i < $entry; $i++) {
            Expense::create([
                "amount" => rand(1, 1000) * -1,
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
                'type' => \Budgetcontrol\Library\Entity\Entry::expenses->value,
                'workspace_id' => $workspace->id,
            ]);
        }

        $output->writeln('Create ' . $entry . ' income');
        Log::debug('Create ' . $entry . ' income');
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

        Log::debug('Create planned income entry');
        $output->writeln('Create planned income entry');
        Income::create([
            "amount" => rand(1, 1000),
            "note" => $faker->sentence(rand(1, 20)),
            "category_id" => 74,
            "account_id" => $wallet->id,
            "currency_id" => $currency_id,
            "payment_type_id" => $payment_type_id,
            "date_time" => $dateTime->modify('+10 days')->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 0,
            "confirmed" => 1,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'type' => \Budgetcontrol\Library\Entity\Entry::incoming->value,
            'workspace_id' => $workspace->id,
        ]);

        // Create a Model
        Log::debug('Create a Model');
        $output->writeln('Create a Model');
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

        // Create a Payee
        Log::debug('Create a Payee');
        $output->writeln('Create a Payee');
        Payee::create([
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'Payee 1 Demo',
            'workspace_id' => $workspace->id,
        ]);

        // Create a PlannedEntry
        Log::debug('Create a PlannedEntry');
        $output->writeln('Create a PlannedEntry');
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

        // Create a goal
        Log::debug('Create a Goal');
        $output->writeln('Create a Goal');
        $goals = [
            [
                'workspace_id' => 1,
                'name' => 'Vatation on the beach',
                'amount' => rand(1, 10000),
                'description' => 'Vacation on the beach in the summer',
                'due_date' => $dateTime->format('Y-m-d H:i:s'),
                'status' => 'active',
                'category_icon' => 'fa-solid fa-suitcase',
                'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString()
            ]
        ];

        foreach ($goals as $goalData) {
            $goal = new Goal();
            $goal->workspace_id = $goalData['workspace_id'];
            $goal->name = $goalData['name'];
            $goal->amount = $goalData['amount'];
            $goal->description = $goalData['description'] ?? null;
            $goal->due_date = $goalData['due_date'] ?? null;
            $goal->status = $goalData['status'] ?? 'active';
            $goal->category_icon = $goalData['category_icon'] ?? null;
            $goal->uuid = $goalData['uuid'];
            $goal->save();

            Saving::create([
            "amount" => rand(1, 1000),
            "note" => $faker->sentence(rand(1, 20)),
            "category_id" => 74,
            "account_id" => $wallet->id,
            "currency_id" => $currency_id,
            "payment_type_id" => $payment_type_id,
            "date_time" => $dateTime->modify('+10 days')->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 0,
            "confirmed" => 1,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'type' => \Budgetcontrol\Library\Entity\Entry::incoming->value,
            'workspace_id' => $workspace->id,
            'goal_id' => $goal->id,
        ]);
        }

        Log::info('Demo data installed');
        $output->writeln('Demo data installed');

        return Command::SUCCESS;
    }
}
