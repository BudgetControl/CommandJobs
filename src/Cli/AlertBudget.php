<?php

namespace Budgetcontrol\jobs\Cli;

use Budgetcontrol\jobs\Domain\Model\Workspace;
use Budgetcontrol\jobs\Facade\Mail;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Budgetcontrol\jobs\Facade\BudgetControlClient;
use Budgetcontrol\jobs\MailerViews\BudgetExceededView;
use BudgetcontrolLibs\Mailer\View\BudgetExceededView as ViewBudgetExceededView;

/**
 * Class ActivatePlannedEntry
 *
 * This class extends the ActivatePlannedEntryJob class and is responsible for activating a planned entry.
 */
class AlertBudget extends JobCommand
{
    protected string $command = 'budget:is-exceeded';

    public function configure()
    {
        $this->setName($this->command)
            ->setDescription('Check if budget is exceeded')
            ->setHelp("This command check if budget is exceeded");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Log::info('Alert budget');
        $workspaces = Workspace::all();

        foreach($workspaces as $workspace) {
            
            $budgets = BudgetControlClient::budgetStats($workspace->id)->getResult();

            foreach ($budgets as $budget) {
                $toNotify = $this->toNotify($budget['budget']);
                if (!empty($toNotify)) {
                    if (str_replace('%', '', $budget['totalSpentPercentage']) > 70) {
                        $view = new ViewBudgetExceededView();
                        $view->setMessage($budget['budget']['name']);
                        $view->setTotalSPent($budget['totalSpent']);
                        $view->setSpentPercentage($budget['totalSpentPercentage']);
                        $view->setPercentage($budget['totalSpentPercentage']);
                        $className = str_replace('%', '', $budget['totalSpentPercentage']) > 80 ? 'bg-red-600' : 'bg-emerald-600';
                        $view->setClassName($className);
    
                        try {
                            Mail::send($toNotify, "Budget exceeded", $view);
                        } catch (\Throwable $e) {
                            $this->fail($e->getMessage());
                            Log::error($e->getMessage());
                            return Command::FAILURE;
                        }
                        
                    }
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
