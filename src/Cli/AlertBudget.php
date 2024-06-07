<?php

namespace Budgetcontrol\jobs\Cli;

use Budgetcontrol\Connector\Client\BudgetClient;
use Budgetcontrol\jobs\Facade\Mail;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Budgetcontrol\SdkMailer\Service\MailerClientService;
use Budgetcontrol\jobs\Domain\Repository\BudgetRepository;
use Budgetcontrol\jobs\Facade\BudgetControlClient;
use Budgetcontrol\jobs\MailerViews\BudgetExceededView;

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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Log::info('Alert budget');
        $budgets = BudgetControlClient::budgetStats(1)->getResult();

        foreach ($budgets as $budget) {
            $toNotify = $this->toNotify($budget['budget']);
            if (!empty($toNotify)) {
                if (str_replace('%', '', $budget['totalSpentPercentage']) > 70) {
                    $view = new BudgetExceededView();
                    $view->setTemplate('budget.twig');
                    $view->setMessage($budget['budget']['name']);
                    $view->setTotalSPent($budget['totalSpent']);
                    $view->setSpentPercentage($budget['totalSpentPercentage']);
                    $view->setPercentage($budget['totalSpentPercentage']);
                    $className = str_replace('%', '', $budget['totalSpentPercentage']) > 80 ? 'bg-red-600' : 'bg-emerald-600';
                    $view->setClassName($className);
                    $view->setName("");
                    
                    Mail::sendMail($toNotify, "Budget exceeded", $view);
                }
            }
        }

        $this->heartbeats(env('HEARTBEAT_BUDGET_EXCEEDED'));
        return Command::SUCCESS;
    }

    /**
     * Notifies the specified budgets.
     *
     * @param object $budget The budgets to notify.
     * @return array The notified budgets.
     */
    private function toNotify($budget): array
    {
        $toNotify = [];
        // retrive user email
        if ($budget['notification'] == true) {
            $toNotify = $budget['emails'] ?? null;
        }

        return $toNotify;
    }
}
