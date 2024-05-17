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

                    $dateStart = Carbon::parse($configuration->start_date);
                    $dateEnd = Carbon::parse($configuration->end_date);

                    // count days between start and end
                    $days = $dateStart->diffInDays($dateEnd);

                    $now = Carbon::now();
                    if ($now->greaterThan($dateEnd)) {
                        $configuration->start_date = $now->startOfDay();
                        $configuration->end_date = clone $now;
                        $configuration->end_date->addDays($days);

                        $budget->configuration = $configuration;
                        $budget->save();
                    }

                }
            }
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());
        }

        return Command::SUCCESS;
    }
}
