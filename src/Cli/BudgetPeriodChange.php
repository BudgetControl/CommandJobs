<?php

namespace Budgetcontrol\jobs\Cli;

use Illuminate\Support\Carbon;
use Budgetcontrol\jobs\Cli\JobCommand;
use Budgetcontrol\jobs\Domain\Model\Budget;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BudgetPeriodChange extends JobCommand
{
    protected string $command = 'change-budget-period';

    public function configure()
    {
        $this->setName($this->command)
            ->setDescription('Change budget period only for recursive budgets')
            ->setHelp("This command change budget period only for recursive budgets");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //TODO: improve this code
        $budgets = Budget::all();

        try {
            foreach ($budgets as $budget) {
                $configuration = json_decode($budget->configuration);
                if ($configuration->period == 'recursively') {

                    $dateStart = Carbon::parse($configuration->period_start);
                    $dateEnd = Carbon::parse($configuration->period_end);

                    // count days between start and end
                    $days = $dateStart->diffInDays($dateEnd);

                    $now = Carbon::now();
                    if ($now->greaterThan($dateEnd)) {
                        $configuration->period_start = $now->startOfDay();
                        $configuration->period_end = clone $now;
                        $configuration->period_end->addDays($days);

                        $budget->configuration = $configuration;
                        $budget->save();
                    }

                }
            }
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());
        }

        $this->heartbeats(env('HEARTBEAT_BUDGET_PERIOD_CHANGE'));
        return Command::SUCCESS;
    }
}
