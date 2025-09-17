<?php

namespace Budgetcontrol\jobs\Cli;

use Budgetcontrol\Connector\Client\BudgetClient;
use Budgetcontrol\jobs\Facade\Mail;
use Budgetcontrol\jobs\Traits\Notify;
use Budgetcontrol\jobs\Domain\Entities\NotificationData;
use Illuminate\Support\Facades\Log;
use Budgetcontrol\jobs\Facade\Crypt;
use Budgetcontrol\Library\Model\User;
use Budgetcontrol\jobs\Cli\JobCommand;
use Budgetcontrol\Library\Model\Budget;
use Symfony\Component\Console\Command\Command;
use Budgetcontrol\Library\Model\Currency;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BudgetcontrolLibs\Mailer\View\BudgetExceededView as ViewBudgetExceededView;
use Illuminate\Database\Capsule\Manager as DB;
use Budgetcontrol\Library\Model\Workspace;
use Illuminate\Support\Facades\Facade;

/**
 * Class ActivatePlannedEntry
 *
 * This class extends the ActivatePlannedEntryJob class and is responsible for activating a planned entry.
 */
class AlertBudget extends JobCommand
{
    use Notify;
    protected string $command = 'budget:email-exceeded';
    private BudgetClient $budgetClient;

    private const WARNING_THRESHOLD = 70;
    private const CRITICAL_THRESHOLD = 90;
    private const EXCEEDED_THRESHOLD = 100;

    public function setCacheKey(string $key): void
    {
        $this->setNotifyKey("budget_email_{$key}");
    }

    public function __construct()
    {
        $logger = Facade::getFacadeApplication();
        $this->budgetClient = new BudgetClient('http://budgetcontrol-ms-budget', $logger['log']);

        parent::__construct();
    }

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
        $this->output = $output;

        foreach ($workspaces as $workspace) {

            //check if workspace has budget
            $hasBudget = Budget::where('workspace_id', $workspace->id)->exists();
            if (!$hasBudget) {
                Log::info("No budgets found for workspace: $workspace->uuid");
                continue;
            }

            try {
                $budgetStats = $this->budgetClient->getAllStats($workspace->id);
            } catch (\Throwable $e) {
                Log::error("Error fetching budgets for workspace: $workspace->uuid - " . $e->getMessage());
                continue;
            }

            if (false === $budgetStats->isSuccessful()) {
                if ($budgetStats->getStatusCode() == 404) {
                    Log::info("No budgets found for workspace: $workspace->uuid");
                    continue;
                }

                $this->fail($budgetStats->getBody());
                return Command::FAILURE;
            }

            $budgets = $budgetStats->toArray();
            if (empty($budgets)) {
                Log::info("No budgets found for workspace: $workspace->uuid");
                continue;
            }

            Log::debug("Found " . count($budgets) . " budgets for workspace: $workspace->uuid");

            foreach ($budgets as $budget) {
                $toNotify = $this->toNotify($budget['budget']);
                if (!empty($toNotify)) {

                    foreach ($toNotify as $email) {

                        $user = $this->findUserFromEmail($email);
                        if ($user == null) {
                            Log::error("User not found with email: $email");
                            continue;
                        }

                        $workspaceId = $budget['budget']['workspace_id'];

                        foreach ($this->getUserWorkspace($user->id) as $userWorkspace) {
                            if ($userWorkspace->id == $workspaceId) {
                                $workspace = $userWorkspace;
                                break;
                            }
                        }

                        /** @var \Budgetcontrol\Library\ValueObject\WorkspaceSetting $wsSettings */
                        $wsSettings = $workspace->workspaceSettings->data;
                        $currency = Currency::find($wsSettings->getCurrency());
                        $currencySymbol = $currency->icon;

                        Log::debug("Checking budget for user: $email in workspace: $workspace->uuid");

                        $spentPercentage = (float) str_replace('%', '', subject: $budget['totalSpentPercentage']);

                        try {
                            $this->handleBudgetEmails($budget, $spentPercentage, $user, $currencySymbol);
                        } catch (\Throwable $e) {
                            Log::critical($e->getMessage());
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
            $toNotify = explode(',', $budget['emails']) ?? null;
        }

        return $toNotify;
    }

    /**
     * Find a user based on their email address.
     *
     * @param string $email The email address of the user.
     * @return ?User The user object if found, null otherwise.
     */
    private function findUserFromEmail($email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Retrieves the workspace associated with a specific user.
     *
     * @param int $userId The ID of the user whose workspace is to be retrieved.
     * @return \Illuminate\Database\Eloquent\Collection The workspace associated with the specified user.
     */
    public function getUserWorkspace(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        $query = "SELECT workspace_id FROM workspaces_users_mm where user_id = ?";
        $resultsQUery = DB::select($query, [$userId]);

        $workspaceIds = [];
        foreach ($resultsQUery as $result) {
            $workspaceIds[] = $result->workspace_id;
        }

        return Workspace::whereIn('id', $workspaceIds)->get();
    }

    private function handleBudgetEmails(array $budget, float $spentPercentage, User $user, string $currencySymbol): void
    {
        // Warning threshold (70%)
        if ($spentPercentage >= self::WARNING_THRESHOLD && $spentPercentage < self::CRITICAL_THRESHOLD) {
            $this->setCacheKey("warning_{$budget['budget']['id']}_{$user->uuid}");
            if (!$this->checkIfNotificationSent()) {
                Mail::budgetExceeded(
                    [
                        $user->email,
                        $budget['budget']['name'],
                        $budget['totalSpent'] * -1,
                        $budget['total'],
                        $currencySymbol,
                        $user->name,
                    ]
                );
                $this->cacheNotificationFlag(7); // Cache per 7 giorni
            }
        }

        // Critical threshold (90%)
        if ($spentPercentage >= self::CRITICAL_THRESHOLD && $spentPercentage < self::EXCEEDED_THRESHOLD) {
            $this->setCacheKey("critical_{$budget['budget']['id']}_{$user->uuid}");
            if (!$this->checkIfNotificationSent()) {
                Mail::budgetExceeded(
                    data: [
                        $user->email,
                        $budget['budget']['name'],
                        $budget['totalSpent'] * -1,
                        $budget['total'],
                        $currencySymbol,
                        $user->name
                    ],
                );
                $this->cacheNotificationFlag(3); // Cache per 3 giorni
            }
        }

        // Exceeded threshold (100%)
        if ($spentPercentage >= self::EXCEEDED_THRESHOLD) {
            $this->setCacheKey("exceeded_{$budget['budget']['id']}_{$user->uuid}");
            if (!$this->checkIfNotificationSent()) {
                Mail::budgetExceeded(
                    [
                        $user->email,
                        $budget['budget']['name'],
                        $budget['totalSpent'] * -1,
                        $budget['total'],
                        $currencySymbol,
                        $user->name
                    ]
                );
                $this->cacheNotificationFlag(1); // Cache per 1 giorno
            }
        }
    }
}
