<?php

namespace Budgetcontrol\jobs\Cli;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Budgetcontrol\jobs\Domain\Model\Entry;
use Budgetcontrol\jobs\Domain\Model\PlannedEntry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Budgetcontrol\jobs\Domain\Repository\PlannedEntryRepository;
use Throwable;

/**
 * Class AddPlannedEntry
 * @package Budgetcontrol\jobs\Cli
 */
class AddPlannedEntry extends JobCommand
{
    protected string $command = 'add-planned-entry';
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
            foreach (self::TIME as $time) {
                $entries = $this->getPlannedEntry($time);
                if ($entries === false) {
                    return Command::INVALID;
                }
    
                $this->insertEntry(
                    $entries
                );
            }
    
            return Command::SUCCESS;
        } catch(Throwable $e) {
            Log::error('Error adding planned entry: ' . $e->getMessage());
            $this->fail($e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Get planned entry based on time
     * @param string $time
     * @return mixed
     */
    private function getPlannedEntry(string $time)
    {
        $entries = PlannedEntryRepository::plannedEntriesFromDateTime(Carbon::now()->format('Y-m-d'));

        if (is_null($entries)) {
            Log::warning('No entries found to save');
            return false;
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

                $entryToInsert = new Entry(['workspace_id' => $entry->workspace_id]);
                $entryToInsert->transfer = 0;
                $entryToInsert->amount = $entry->amount;
                $entryToInsert->account_id = $entry->account_id;
                $entryToInsert->category_id = $entry->category_id;
                $entryToInsert->type = $entry->type;
                $entryToInsert->waranty = 0;
                $entryToInsert->confirmed = 1;
                $entryToInsert->planned = 1;
                $entryToInsert->date_time = Carbon::rawCreateFromFormat('Y-m-d h:i:s', $entry->date_time)->toAtomString();
                $entryToInsert->note = $entry->note;
                $entryToInsert->currency_id = $entry->currency_id;
                $entryToInsert->uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
                $entryToInsert->save();

                //save tags
                foreach($entry->tags as $tag) {
                    $entryToInsert->tags()->attach($tag->id);
                }
            }

            $this->updatePlanningEntry($data);
    }

    /**
     * Update planning entry to the next date
     */
    private function updatePlanningEntry(array $data)
    {
        foreach ($data as $e) {
            $date = $this->getTimeValue($e->planning);
            PlannedEntry::find($e->id)->update(
                [
                    'date_time' => $date->toAtomString(),
                    'updated_at' => Carbon::now()->toAtomString()
                ]
            );
        }
    }

    /**
     * Get the time value based on timing
     * @param string $timing
     * @return Carbon
     */
    private function getTimeValue(string $timing): Carbon
    {
        $date = Carbon::now();

        switch ($timing) {
            case "daily":
                $newDate = $date->modify('+1 day');
                break;
            case "monthly":
                $newDate = $date->modify('+1 month');
                break;
            case "weekly":
                $newDate = $date->modify('+7 days');
                break;
            case "yearly":
                $newDate = $date->modify('+1 year');
                break;
            default:
                $newDate = $date;
                break;
        }

        return $newDate;
    }
}
