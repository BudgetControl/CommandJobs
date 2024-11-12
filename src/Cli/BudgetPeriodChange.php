<?php

namespace Budgetcontrol\jobs\Cli;

use Illuminate\Support\Carbon;
use Budgetcontrol\jobs\Cli\JobCommand;
use Budgetcontrol\Library\Definition\Format;
use Budgetcontrol\Library\Model\Budget;
use Budgetcontrol\Library\Definition\Period;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Budgetcontrol\Library\ValueObject\BudgetConfiguration;

class BudgetPeriodChange extends JobCommand
{
    protected string $command = 'budget:is-expired';

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

                /** @var \Budgetcontrol\Library\ValueObject\BudgetConfiguration $configuration */
                $configuration = $budget->configuration;
                if ($configuration->getPeriod() == Period::recursively->value) {

                    $dateStart = $configuration->getPeriodStart();
                    $dateEnd = $configuration->getPeriodEnd();

                    // count days between start and end
                    $days = $dateStart->diff($dateEnd);

                    $now = Carbon::now();
                    $newConfiguration = $configuration->toJson();
                    if ($now->greaterThan($dateEnd)) {
                        $newConfiguration->period_start = $now->startOfDay();
                        $newConfiguration->period_end = clone $now;
                        $newConfiguration->period_end->addDays($days->days);

                        $configuration = BudgetConfiguration::create(
                            $newConfiguration->tags,
                            $newConfiguration->types,
                            $newConfiguration->period,
                            $newConfiguration->accounts,
                            $newConfiguration->categories,
                            $newConfiguration->period_end->format(Format::dateTime->value),
                            $newConfiguration->period_start->format(Format::dateTime->value)
                        );

                        $budget->configuration = $configuration;
                        $budget->save();
                    }

                }
            }
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());
            return Command::FAILURE;
        }

        $this->heartbeats(env('HEARTBEAT_BUDGET_PERIOD_CHANGE'));
        return Command::SUCCESS;
    }
}
