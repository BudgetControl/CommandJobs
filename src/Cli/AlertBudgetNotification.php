<?php

namespace Budgetcontrol\jobs\Cli;

use Budgetcontrol\Connector\Client\BudgetClient;
use Budgetcontrol\jobs\Traits\Notify;
use Budgetcontrol\jobs\Domain\Entities\NotificationData;
use Illuminate\Support\Facades\Log;
use Budgetcontrol\Library\Model\User;
use Budgetcontrol\Library\Model\Budget;
use Symfony\Component\Console\Command\Command;
use Budgetcontrol\Library\Model\Currency;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Database\Capsule\Manager as DB;
use Budgetcontrol\Library\Model\Workspace;
use Illuminate\Support\Facades\Facade;

class AlertBudgetNotification extends JobCommand
{
    use Notify;
    protected string $command = 'budget:notify-threshold';
    private BudgetClient $budgetClient;
    
    private const WARNING_THRESHOLD = 70;
    private const CRITICAL_THRESHOLD = 90;
    private const EXCEEDED_THRESHOLD = 100;

    public function __construct()
    {
        $logger = Facade::getFacadeApplication();
        $this->budgetClient = new BudgetClient('http://budgetcontrol-ms-budget', env('API_SECRET'));
        parent::__construct();
    }

    public function configure()
    {
        $this->setName($this->command)
            ->setDescription('Send push notifications for budget thresholds')
            ->setHelp("This command sends push notifications when budgets reach warning or exceeded thresholds");
    }

    public function setCacheKey(string $key): void
    {
        $this->setNotifyKey("budget_notification_{$key}");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Log::info('Checking budget thresholds for notifications');
        $this->output = $output;

        foreach (Workspace::all() as $workspace) {
            if (!Budget::where('workspace_id', $workspace->id)->exists()) {
                continue;
            }

            try {
                $budgetStats = $this->budgetClient->getBudgetsStats($workspace->id);
                if (!$budgetStats->isSuccessful() || empty($budgetStats->toArray())) {
                    continue;
                }

                foreach ($budgetStats->toArray() as $budget) {
                    $this->processNotifications($budget, $workspace);
                }

            } catch (\Throwable $e) {
                Log::error("Error processing workspace {$workspace->uuid}: " . $e->getMessage());
                continue;
            }
        }

        $this->heartbeats(env('HEARTBEAT_BUDGET_NOTIFICATION'));
        return Command::SUCCESS;
    }

    private function processNotifications(array $budget, Workspace $workspace): void
    {
        $toNotify = $this->toNotify($budget['budget']);
        if (empty($toNotify)) {
            return;
        }

        $wsSettings = $workspace->workspaceSettings->data;
        $currency = Currency::find($wsSettings->getCurrency());
        $currencySymbol = $currency->icon;
        $spentPercentage = (float)str_replace('%', '', $budget['totalSpentPercentage']);

        foreach ($toNotify as $email) {
            $user = User::where('email', $email)->first();
            if (!$user) continue;

            try {
                if ($spentPercentage >= self::WARNING_THRESHOLD && $spentPercentage < self::CRITICAL_THRESHOLD) {
                    $this->sendWarningNotification($user, $budget, $spentPercentage, $currencySymbol);
                } elseif ($spentPercentage >= self::CRITICAL_THRESHOLD && $spentPercentage < self::EXCEEDED_THRESHOLD) {
                    $this->sendCriticalNotification($user, $budget, $spentPercentage, $currencySymbol);
                } elseif ($spentPercentage >= self::EXCEEDED_THRESHOLD) {
                    $this->sendExceededNotification($user, $budget, $spentPercentage, $currencySymbol);
                }
            } catch (\Throwable $e) {
                Log::error("Error sending notification to {$user->email}: " . $e->getMessage());
            }
        }
    }

    private function sendWarningNotification(User $user, array $budget, float $spentPercentage, string $currencySymbol): void
    {
        $this->setCacheKey("warning_{$budget['budget']['id']}_{$user->uuid}");
        $this->notify(new NotificationData(
            $user->uuid,
            "Il budget {$budget['budget']['name']} ha raggiunto il {$spentPercentage}% ({$currencySymbol}{$budget['totalSpent']})",
            "Avviso Budget"
        ));
    }

    private function sendExceededNotification(User $user, array $budget, float $spentPercentage, string $currencySymbol): void
    {
        $this->setCacheKey("exceeded_{$budget['budget']['id']}_{$user->uuid}");
        $this->notify(new NotificationData(
            $user->uuid,
            "Il budget {$budget['budget']['name']} è stato superato! ({$currencySymbol}{$budget['totalSpent']})",
            "Budget Superato"
        ), true);
    }

    private function sendCriticalNotification(User $user, array $budget, float $spentPercentage, string $currencySymbol): void
    {
        $this->setCacheKey("critical_{$budget['budget']['id']}_{$user->uuid}");
        $this->notify(new NotificationData(
            $user->uuid,
            "Attenzione: il budget {$budget['budget']['name']} ha raggiunto il {$spentPercentage}% ({$currencySymbol}{$budget['totalSpent']})",
            "Budget Quasi Esaurito"
        ));
    }

    private function toNotify($budget): array
    {
        if ($budget['notification'] == true) {
            return explode(',', $budget['emails']) ?? [];
        }
        return [];
    }
}