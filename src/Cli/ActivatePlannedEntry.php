<?php

namespace Budgetcontrol\jobs\Cli;

use Throwable;
use Illuminate\Support\Facades\Log;
use Budgetcontrol\jobs\Domain\Repository\EntryRepository;
use Budgetcontrol\jobs\Service\NotificationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Budgetcontrol\Library\Model\Wallet;
use Budgetcontrol\Library\Model\Entry;
use Budgetcontrol\Library\Model\Workspace;

/**
 * Class ActivatePlannedEntry
 *
 * This class extends the ActivatePlannedEntryJob class and is responsible for activating a planned entry.
 */
class ActivatePlannedEntry extends JobCommand
{
    protected string $command = 'entry:activate-planned';
    private NotificationService $notificationService;

    public function configure()
    {
        $this->setName($this->command)
            ->setDescription('Activate a planned entry')
            ->setHelp("This command allows you to activate a planned entry");
    }

    /**
     * Executes the command to activate a planned entry.
     *
     * @param InputInterface $input The input interface.
     * @param OutputInterface $output The output interface.
     * @return int The exit code.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->notificationService = new NotificationService();
        $repository = new EntryRepository();
        Log::info('Activating planned entries');
        $this->output = $output;
        try {
            $entries = $repository->entryOfCurrentTime();

            if(is_null($entries)) {
                Log::info('No planned entries to activate');
                $this->heartbeats(env('HEARTBEAT_ACTIVATE_PLANNED_ENTRY'));
                return Command::SUCCESS;
            }

            $workspaceIds = [];
            foreach ($entries as $currentEntry) {
                $workspaceIds[] = $currentEntry->workspace_id;
                $entry = Entry::find($currentEntry->id);
                $entry->planned = false;
                $entry->save();

                $users = $this->findUserUuidByWorkspaceId($currentEntry->workspace_id);
                if (!empty($users)) {
                    $this->sendUserNotification($users, 'Nuova spesa pianificata', 'La spesa "' . $currentEntry->note . '" è stata attivata. ' . $entry->amount . '€'); //FIXME: manage currency
                }

            }

            if(!empty($workspaceIds)) {
                foreach (array_unique($workspaceIds) as $workspaceId) {
                    $this->invokeClearCache('entry', $workspaceId);
                }
            }

            $this->heartbeats(env('HEARTBEAT_ACTIVATE_PLANNED_ENTRY'));
            return Command::SUCCESS;
        } catch (Throwable $e) {
            Log::error('Error activating planned entry: ' . $e->getMessage());
            $this->fail($e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Finds the UUID(s) of user(s) associated with the given workspace ID.
     *
     * @param int $workspaceId The ID of the workspace to search for users.
     * @return array An array of user UUIDs associated with the workspace.
     */
    private function findUserUuidByWorkspaceId(int $workspaceId): array
    {
        $workspace = Workspace::with('users')->find($workspaceId);

        if ($workspace) {
            return $workspace->users->pluck('uuid')->toArray();
        }

        Log::warning("No users found for workspace ID: {$workspaceId}");
        
        return [];
    }

    protected function sendUserNotification(array $userUuids, string $title, string $body): void
    {
        foreach ($userUuids as $userUuid) {
            $this->notificationService->sendPushNotificationToUser($userUuid, $title, $body);
        }
    }
}
