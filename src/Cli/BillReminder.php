<?php

namespace Budgetcontrol\jobs\Cli;

use Budgetcontrol\jobs\Facade\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Budgetcontrol\jobs\Cli\JobCommand;
use Budgetcontrol\Library\Model\Entry;
use Budgetcontrol\jobs\Facade\Notification;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Support\Facades\Cache as SysCache;


class BillReminder extends JobCommand
{
    protected string $command = 'entry:check-bill-reminder';

    private const ENTRY_TYPES = [
        \Budgetcontrol\Library\Entity\Entry::incoming->value,
        \Budgetcontrol\Library\Entity\Entry::expenses->value,
        \Budgetcontrol\Library\Entity\Entry::debit->value
    ];

    public function configure()
    {
        $this->setName($this->command)
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Number of days to check for planned entries', 2)
            ->setDescription('Check bill reminders')
            ->setHelp("This command check if there are any bill reminders");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        Log::info('Check bill reminders');
        $days = (int)$input->getOption('days');

        $entries = $this->findFuturePlannedEntries($days);
        foreach ($entries as $entry) {
            Log::debug("Found planned entry {$entry->id} for date {$entry->date_time}");
            $user = $this->getUserFromWorkspaceId($entry->workspace_id);
            if(is_null($user)) {
                Log::warning("No user found for workspace {$entry->workspace_id}, skipping entry {$entry->id}");
                continue;
            }
            if($this->checkIfNotificationSent($entry->id, $user->uuid) === true) {
                Log::info("Notification already sent for entry {$entry->id} to user {$user->uuid}, skipping.");
                continue;
            }

            // build message and push a notification
            $message = "You have a planned entry for {$entry->date_time} with amount {$entry->amount}\n";
            try {
                Notification::sendNotification(new \Budgetcontrol\jobs\Domain\Entities\NotificationData(
                    $user->uuid,
                    $message,
                ));
            } catch (\Exception $e) {
                $this->fail("Failed to send notification for entry {$entry->id}: {$e->getMessage()}");
                return Command::FAILURE;
            }

            // If notification sent, save it on cache
            $this->cacheNotificationFlag($entry->id, $user->uuid, $days);

        }

        $this->heartbeats(null);
        return Command::SUCCESS;
    }

    /**
     * Finds planned entries that are scheduled for the future within a specified number of days.
     *
     * @param int $days The number of days ahead to look for planned entries. Defaults to 2.
     * @return array The list of future planned entries found within the specified time frame.
     */
    private function findFuturePlannedEntries(int $days = 3)
    {
        // find all planned entry will be active in next $days days
        $date = new \DateTime();
        $date->modify("+$days days");
        $dateString = $date->format('Y-m-d H:i:s');

        $entries = Entry::where('date_time', '<=', $dateString)
        ->where('planned', true)
        ->whereIn('type', self::ENTRY_TYPES)
        ->get()
        ->all();

        return $entries;
    }

    /**
     * Caches the notification flag for a specific bill and user
     * 
     * @param int $id The bill identifier
     * @param string $user_uuid The UUID of the user
     * @param int $ttl The time to live in days for the cache entry (default is 3 days)
     * @return void
     */
    private function cacheNotificationFlag(int $id, string $user_uuid, int $ttl = 3): void
    {
        $cacheKey = "bill_reminder_{$user_uuid}_{$id}";
        SysCache::put($cacheKey, true, $ttl * 60 * 60 * 24); // Cache for the same days
    }

    /**
     * Checks if a notification has already been sent for a specific bill and user.
     *
     * @param int $id The bill ID to check
     * @param string $user_uuid The UUID of the user to check
     * @return bool Returns true if notification was not sent yet, false otherwise
     */
    private function checkIfNotificationSent(int $id, string $user_uuid): bool
    {
        $cacheKey = "bill_reminder_{$user_uuid}_{$id}";
        return SysCache::has($cacheKey);
    }

}
