<?php

namespace Budgetcontrol\jobs\Cli;

use Throwable;
use Brick\Math\BigNumber;
use Illuminate\Support\Facades\Log;
use Budgetcontrol\jobs\Domain\Model\Entry;
use Budgetcontrol\jobs\Domain\Model\Wallet;
use Budgetcontrol\jobs\Domain\Repository\EntryRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ActivatePlannedEntry
 *
 * This class extends the ActivatePlannedEntryJob class and is responsible for activating a planned entry.
 */
class ActivatePlannedEntry extends JobCommand
{
    protected string $command = 'entry:activate-planned';

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
}
