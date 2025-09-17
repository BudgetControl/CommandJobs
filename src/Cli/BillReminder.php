<?php

namespace Budgetcontrol\jobs\Cli;

use Budgetcontrol\jobs\Facade\Cache;
use Budgetcontrol\jobs\Traits\Notify;
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
    use Notify;
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

            // build message and push a notification
            //FIXME: should be in user language $user->language 
            $message = $this->message($entry, 'it');
            $title = $this->title('it');
            try {
                
                $dataToNotify = new \Budgetcontrol\jobs\Domain\Entities\NotificationData(
                    $user->uuid,
                    $message,
                    $title,
                );
                $this->notify($dataToNotify);

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
    protected function cacheNotificationFlag(int $id, string $user_uuid, string $cache_key,int $ttl = 3): void
    {
        $cacheKey = "{$cache_key}_{$user_uuid}_{$id}";
        SysCache::put($cacheKey, true, $ttl * 60 * 60 * 24); // Cache for the same days
    }

    /**
     * Checks if a notification has already been sent for a specific bill and user.
     *
     * @param int $id The bill ID to check
     * @param string $user_uuid The UUID of the user to check
     * @return bool Returns true if notification was not sent yet, false otherwise
     */
    protected function checkIfNotificationSent(int $id, string $user_uuid, string $cache_key): bool
    {
        $cacheKey = "{$cache_key}_{$user_uuid}_{$id}";
        return SysCache::has($cacheKey);
    }

    private function message(Entry $entry, string $lang): string
    {
        $date = Carbon::parse($entry->date_time)->locale($lang);
        return match ($lang) {
            'en' => "at {$date->format('Y-m-d H:i:s')} with amount {$entry->amount}\n {$entry->note}",
            'es' => "a las {$date->format('Y-m-d H:i:s')} con un monto de {$entry->amount}\n {$entry->note}",
            'it' => "il {$date->format('d/m/Y')} di €{$entry->amount}\n {$entry->note}",
            default => throw new \InvalidArgumentException("Unsupported language: $lang"),
        };
    }

    private function title(string $lang): string
    {
        return match ($lang) {
            'en' => 'Bill Reminder',
            'es' => 'Recordatorio de factura',
            'it' => 'Pagamento in scadenza',
            default => throw new \InvalidArgumentException("Unsupported language: $lang"),
        };
    }

}
