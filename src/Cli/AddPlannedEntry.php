<?php

namespace Budgetcontrol\jobs\Cli;

use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Budgetcontrol\jobs\Domain\Repository\PlannedEntryRepository;
use Budgetcontrol\Library\Definition\Format;
use Budgetcontrol\Registry\Schema\Entries;
use Ramsey\Uuid\Uuid;
use Throwable;
use Carbon\Carbon;
use Budgetcontrol\Library\Model\Entry;
use Budgetcontrol\Library\Model\PlannedEntry;
use Budgetcontrol\jobs\Domain\Planning\Recurrence;
use Illuminate\Database\Capsule\Manager as DB;
use DateTimeImmutable;

/**
 * Class AddPlannedEntry
 * @package Budgetcontrol\jobs\Cli
 */
class AddPlannedEntry extends JobCommand
{
    protected string $command = 'entry:add-planned';
    const TIME = [
        'daily', 'weekly', 'monthly', 'yearly'
    ];

    protected array $workspaceIds = [];

    /**
     * Configure the command
     */
    public function configure()
    {
        $this->setName($this->command)
            ->setDescription('Add planned entry')
            ->setHelp("This command allows you to add a new planned entry");
    }

    /**
     * Execute the command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {   
        Log::info('Adding planned entries');
        $this->output = $output;
        try {

            $repository = new PlannedEntryRepository();
            $entries = $repository->plannedEntryOfTheMonth();
            if ($entries !== null) {
                foreach ($entries as $candidate) {
                    DB::transaction(function () use ($candidate) {
                        $entry = PlannedEntry::with('labels')->where('id', $candidate->id)
                            ->whereNull('deleted_at')->lockForUpdate()->first();
                        if ($entry === null) {
                            return;
                        }

                        $next = new DateTimeImmutable((string) $entry->date_time);
                        $end = $entry->end_date_time ? new DateTimeImmutable((string) $entry->end_date_time) : null;
                        [$dates, $next] = Recurrence::occurrences(
                            $next, $entry->period, new DateTimeImmutable('last day of this month 23:59:59'), $end
                        );
                        foreach ($dates as $date) {
                            $occurrence = clone $entry;
                            $occurrence->date_time = $date->format(Format::dateTime->value);
                            $this->insertEntry([$occurrence]);
                        }
                        if ($dates !== []) {
                            $entry->date_time = $next->format(Format::dateTime->value);
                            $entry->save();
                        }
                    });
                }
            }

            foreach (array_unique($this->workspaceIds) as $workspaceId) {
                $this->invokeClearCache('entry', $workspaceId);
            }

            $this->heartbeats(env('HEARTBEAT_PLANNED_ENTRY'));
            return Command::SUCCESS;
        } catch(Throwable $e) {
            Log::error('Error adding planned entry: ' . $e->getMessage());
            $this->fail($e->getMessage());
        }

        return Command::FAILURE;
    }

    /**
     * Insert entry into the database
     * @param mixed $data
     */
    private function insertEntry($data)
    {
            /** @var EntryModel $request  */
            foreach ($data as $entry) {
                $this->workspaceIds[] = $entry->workspace_id;
                $dateTime = Carbon::parse($entry->date_time)->format(Format::dateTime->value);

                $entryToInsert = new Entry([Entries::workspace_id => $entry->workspace_id]);
                $entryToInsert->transfer = 0;
                $entryToInsert->amount = $entry->amount;
                $entryToInsert->account_id = $entry->account_id;
                $entryToInsert->category_id = $entry->category_id;
                $entryToInsert->type = $entry->type;
                $entryToInsert->waranty = 0;
                $entryToInsert->confirmed = 1;
                $entryToInsert->planned = 1;
                $entryToInsert->date_time = $dateTime;
                $entryToInsert->note = $entry->note;
                $entryToInsert->currency_id = $entry->currency_id;
                $entryToInsert->uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
                $entryToInsert->workspace_id = $entry->workspace_id;
                $entryToInsert->save();

                //save labels
                foreach($entry->labels as $label) {
                    $entryToInsert->labels()->attach($label->id);
                }
            }
    }

}
