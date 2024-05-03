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
    protected string $command = 'activate-planned-entry';

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
        Log::info('Activating planned entries');
        try {
            $entries = EntryRepository::entryOfCurrentTime();
            foreach ($entries as $currentEntry) {
                $entry = Entry::find($currentEntry->id);
                //for each entry, update wallet balance
                $walletId = $entry->account_id;
                $wallet = Wallet::find($walletId);

                if($wallet === null) {
                    Log::error('Wallet not found for entry: ' . $entry->id);
                    continue;
                }

                $walletBalance = $wallet->balance;
                $entry->planned = false;
                $entry->save();

                if($entry->confirmed == 1) {
                    $newBalance = BigNumber::sum($walletBalance, $entry->amount);
                    $wallet->balance = $newBalance;
                    $wallet->save();
                }
                
            }

            return Command::SUCCESS;
        } catch (Throwable $e) {
            Log::error('Error activating planned entry: ' . $e->getMessage());
            $this->fail($e->getMessage());
            return Command::FAILURE;
        }
    }
}
