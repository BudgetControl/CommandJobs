<?php

namespace Budgetcontrol\jobs\Cli;

use Throwable;
use Brick\Math\BigNumber;
use Illuminate\Support\Facades\Log;
use Budgetcontrol\jobs\Domain\Model\Entry;
use Budgetcontrol\jobs\Domain\Model\Wallet;
use Budgetcontrol\jobs\Domain\Repository\BudgetRepository;
use Budgetcontrol\jobs\Domain\Repository\PlannedEntryRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ActivatePlannedEntry
 *
 * This class extends the ActivatePlannedEntryJob class and is responsible for activating a planned entry.
 */
class AlertBudget extends JobCommand
{
    protected string $command = 'check-budget-exceeded';

    public function configure()
    {
        $this->setName($this->command)
            ->setDescription('Check if budget is exceeded')
            ->setHelp("This command check if budget is exceeded");
    }

    // protected function execute(InputInterface $input, OutputInterface $output)
    // {
    //     Log::info('Alert budget');
    //     $budgets = BudgetRepository::findExceededBudget();
    //     foreach($budgets as $budget) {
    //         $consiguration = json_decode($budget->configuration);
            
    //     }
    // }
}
