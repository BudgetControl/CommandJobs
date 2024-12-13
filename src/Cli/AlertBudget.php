<?php

namespace Budgetcontrol\jobs\Cli;

use Budgetcontrol\jobs\Facade\Mail;
use Illuminate\Support\Facades\Log;
use Budgetcontrol\jobs\Facade\Crypt;
use Budgetcontrol\Library\Model\User;
use Budgetcontrol\jobs\Cli\JobCommand;
use Symfony\Component\Console\Command\Command;
use Budgetcontrol\jobs\Facade\BudgetControlClient;
use Budgetcontrol\Library\Model\Currency;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BudgetcontrolLibs\Mailer\View\BudgetExceededView as ViewBudgetExceededView;
use Illuminate\Database\Capsule\Manager as DB;
use Budgetcontrol\Library\Model\Workspace;

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

        foreach ($workspaces as $workspace) {

            $budgets = BudgetControlClient::budgetStats($workspace->id)->getResult();

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
                        $currencySymbol = Currency::find($currency)->icon;

                        if (str_replace('%', '', $budget['totalSpentPercentage']) > 70) {
                            $view = new ViewBudgetExceededView();
                            $view->setUserName($user->name);
                            $view->setUserEmail($user->email);
                            $view->setMessage($budget['budget']['name']);
                            $view->setTotalSPent($budget['totalSpent']);
                            $view->setSpentPercentage($budget['totalSpentPercentage']);
                            $view->setPercentage($budget['totalSpentPercentage']);
                            $className = str_replace('%', '', $budget['totalSpentPercentage']) > 80 ? 'bg-red-600' : 'bg-emerald-600';
                            $view->setCurrency($currencySymbol);
                            $view->setTotalRemaining(($budget['totalRemaining'] < 0) ? 0 : $budget['totalRemaining']);
                            $view->setClassName($className);
                            $view->setBudgetAmount($budget['total']);

                            try {
                                Mail::send($user->email, "Budget exceeded", $view);
                            } catch (\Throwable $e) {
                                $this->fail($e->getMessage());
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
        return User::where('email', Crypt::encrypt($email))->first();
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
