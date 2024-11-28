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

    /**
     * Configure the command
     */
    public function configure()
    {
        $this->setName($this->command)
            ->setDescription('Add a planned entry')
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
        try {

            $entries = $this->getPlannedEntry();
            if ($entries === false) {
                $this->heartbeats(env('HEARTBEAT_PLANNED_ENTRY'));
                return Command::INVALID;
            }

            $this->insertEntry(
                $entries
            );
            
            $this->heartbeats(env('HEARTBEAT_PLANNED_ENTRY'));
            return Command::SUCCESS;
        } catch(Throwable $e) {
            Log::error('Error adding planned entry: ' . $e->getMessage());
            $this->fail($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function getPlannedEntry()
    {
        $repository = new PlannedEntryRepository();
        $entries = $repository->plannedEntryOfTheMonth();

        if (is_null($entries)) {
            Log::warning('No entries found to save');
            return false;
        }

        $totalEntries = [];
        foreach ($entries as $entry) {
            
            switch ($entry->planning) {
                case 'daily':
                    $duplicateItems = $this->buildEntriesDays($entry);
                    break;
                case 'weekly':
                    $duplicateItems = $this->buildEntriesWeeks($entry);
                    break;
                case 'monthly':
                    $duplicateItems = [$entry];
                    break;
                case 'yearly':
                    $duplicateItems = [$entry];
                    break;
                default:
                    $duplicateItems = [$entry];
                    break;
            }

            $totalEntries = array_merge($totalEntries, $duplicateItems);
        }

        $this->updatePlanningEntry($entries);
        return $totalEntries;
    }

    private function buildEntriesDays($entry)
    {
        $count = 0;
        $duplicateItems = date('t'); //count current days of the month
        
        $entries = [];
        // first day of the month
        $firstDay = Carbon::now()->startOfMonth()->minute(0)->second(0)->hour(0);
        while($count < $duplicateItems) {
            $day = 1;
            if($count == 0) {
                $day = 0;
            }
            $newEntry = clone $entry;
            $newEntry->uuid = Uuid::uuid4()->toString();

            $newDate = $firstDay->addDays($day);
            if ($newDate->month > Carbon::now()->month) {
                unset($newEntry);
            } else {
                $newEntry->date_time = $newDate->toAtomString();
                $entries[] = $newEntry;
            }
            $count++;
        }

        return $entries;
    }

    private function buildEntriesWeeks($entry)
    {
        $count = 0;
        $duplicateItems = ceil(date('t') / 7); //count weeks in the month

        while($count < $duplicateItems) {
            $newEntry = clone $entry;
            $newEntry->uuid = Uuid::uuid4()->toString();
            $newDate = Carbon::createFromFormat('Y-m-d', date('Y-m-d',strtotime($entry->date_time)))->addWeeks($count);
            //if date_time is nex month swith
            if ($newDate->month > Carbon::now()->month) {
                unset($newEntry);
            } else {
                $newEntry->date_time = $newDate->toAtomString();
                $entries[] = $newEntry;
            }
            $count++;
        }

        return $entries;
    }

    /**
     * Insert entry into the database
     * @param mixed $data
     */
    private function insertEntry($data)
    {
            /** @var EntryModel $request  */
            foreach ($data as $entry) {

                $dateTime = Carbon::createFromFormat('Y-m-d', date('Y-m-d',strtotime($entry->date_time)))->format(Format::dateTime->value);

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

                //save tags
                foreach($entry->tags as $tag) {
                    $entryToInsert->labels()->attach($tag->id);
                }
            }
    }

    /**
     * Updates a planning entry.
     *
     * @param array $entries The entries to update.
     * @return void
     */
    private function updatePlanningEntry($entries)
    {
        foreach ($entries as $e) {
            PlannedEntry::find($e->id)->update(
                [
                    'date_time' => Carbon::createFromFormat('Y-m-d', date('Y-m-d',strtotime($e->date_time)))->addMonth()->format(Format::dateTime->value),
                    'updated_at' => Carbon::now()->format(Format::dateTime->value)
                ]
            );
        }
    }
    
}
