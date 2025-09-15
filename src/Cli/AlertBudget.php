<?php

namespace Budgetcontrol\jobs\Cli;

use Budgetcontrol\Connector\Client\BudgetClient;
use Budgetcontrol\Connector\Client\MailerClient;
use Budgetcontrol\Connector\Client\StatsClient;
use Budgetcontrol\Connector\Entities\Payloads\Mailer\Budget\BudgetMailer;
use Illuminate\Support\Facades\Log;
use Budgetcontrol\Library\Model\User;
use Budgetcontrol\jobs\Cli\JobCommand;
use Budgetcontrol\jobs\Facade\BudgetControlClient;
use Budgetcontrol\Library\Model\Budget;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
    protected string $command = 'budget:is-exceeded';
    private MailerClient $mailerClient;
    private BudgetClient $budgetClient;

    public function __construct()
    {
        $this->mailerClient = BudgetControlClient::mailer();
        $this->budgetClient = BudgetControlClient::budget();

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
                // @depreceated use uuid instead of id
                $budgetStats = $this->budgetClient->getBudgetsStats($workspace->id);
            } catch (\Throwable $e) {
                Log::error("Error fetching budgets for workspace: $workspace->uuid - " . $e->getMessage());
                continue;
            }

            if(false === $budgetStats->isSuccessful()) {
                if($budgetStats->getStatusCode() == 404) {
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

                        foreach($this->getUserWorkspace($user->id) as $userWorkspace) {
                            if ($userWorkspace->id == $workspaceId) {
                                $workspace = $userWorkspace;
                                break;
                            }
                        }

                        /** @var \Budgetcontrol\Library\ValueObject\WorkspaceSetting $wsSettings */
                        $wsSettings =  $workspace->workspaceSettings->data;
                        $currency = $wsSettings->getCurrency();
                        $currencySymbol = $currency['icon'];

                        Log::debug("Checking budget for user: $email in workspace: $workspace->uuid");

                        if (str_replace('%', '', $budget['totalSpentPercentage']) > 70) {
                            try {
                                Log::debug("Sending budget exceeded notification to: $email");

                                $mailerPayload = new BudgetMailer($user->email, $budget['budget']['name'], $budget['totalSpent'] * -1, $budget['total'], $currencySymbol, $user->name);
                                $this->mailerClient->budgetExceeded($mailerPayload);
                                
                            } catch (\Throwable $e) {
                                Log::critical($e->getMessage());
                                return Command::FAILURE;
                            }
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
            $toNotify = explode(',',$budget['emails']) ?? null;
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
}
