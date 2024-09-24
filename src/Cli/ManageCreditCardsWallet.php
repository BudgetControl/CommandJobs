<?php

namespace Budgetcontrol\jobs\Cli;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Webit\Wrapper\BcMath\BcMathNumber;
use Budgetcontrol\jobs\Domain\Model\Entry;
use Budgetcontrol\jobs\Domain\Model\Wallet;
use Budgetcontrol\Library\Definition\Format;
use Budgetcontrol\Library\Entity\Entry as EntityEntry;
use Budgetcontrol\Library\Entity\Wallet as EntityWallet;
use Budgetcontrol\Registry\Schema\Wallets;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * FILEPATH: /Users/marco/Projects/marco/BC/Core/microservices/CommandJobs/src/Cli/ManageCreditCardsWallet.php
 *
 * The ManageCreditCardsWallet class is responsible for managing credit cards in the wallet.
 * It extends the JobCommand class.
 */
class ManageCreditCardsWallet extends JobCommand
{
    protected string $command = 'wallet:update-credit-card';

    public function configure()
    {
        $this->setName($this->command)
            ->setDescription('Manage credit cards in the wallet')
            ->setHelp("This command allows you to manage credit cards in the wallet, such as adding, removing, and updating credit cards invoice date and recursive payments.");
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
        Log::info('Managing credit cards');

        $creditCards = Wallet::whereIn('type', [EntityWallet::creditCard->value, EntityWallet::creditCardRevolving->value])
            ->whereBetween(Wallets::invoice_date, [
            Carbon::now()->startOfMonth()->format(Format::date->value),
            Carbon::now()->endOfMonth()->format(Format::date->value)
            ])
            ->where('balance', '<', 0)
            ->get();

        try {
            foreach ($creditCards as $creditCard) {
                if($this->conditions($creditCard) === true) {
                    $this->creditCard($creditCard);
                }
            }
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());
            return Command::FAILURE;
        }

        $this->heartbeats(env('HEARTBEAT_MANAGE_CREDIT_CARDS'));
        Log::info('Credit cards managed successfully');
        return Command::SUCCESS;
    }

    private function creditCard(Wallet $creditCard)
    {
        $creditCardEntry = $this->saveEntry($creditCard);
        $walletEntry = $this->saveEntry($creditCard);

        $creditCardEntry->transfer_relation = $walletEntry->uuid;
        $walletEntry->transfer_relation = $creditCardEntry->uuid;
        $walletEntry->amount = $creditCard->installement_value * -1; // negative value for related entry

        $creditCardEntry->save();
        $walletEntry->save();

        $installementValue = $creditCard->installement_value;
        //calculate the wallet balance
        $balance = new BcMathNumber($creditCard->balance);
        $creditCard->balance = $balance->add($installementValue)->getValue();
        if($creditCard->balance > 0) {
            $installementValue = $creditCard->balance;
            $creditCard->balance = 0;
        }

        $wallet = Wallet::find($creditCard->payment_account);
        $walletBalance = new BcMathNumber($wallet->balance);
        $wallet->balance = $walletBalance->sub($installementValue)->getValue();
        $wallet->save();

        // move date to next month
        $newInvoiceDate = Carbon::parse($creditCard->invoice_date)->addMonth();
        $newClosingDate = Carbon::parse($creditCard->closing_date)->addMonth();

        $creditCard->invoice_date = $newInvoiceDate->format(Format::date->value . ' 00:00:00');
        $creditCard->closing_date = $newClosingDate->format(Format::date->value . ' 00:00:00');
        $creditCard->save();

        log::debug('Credit card updated', ['creditCard' => $creditCard->toArray()]);

    }

    private function saveEntry(Wallet $creditCard): Entry
    {

        // se installement_value è una percentuale
        $amount = $creditCard->installement_value;

        if($creditCard->type == EntityWallet::creditCard->value) {
            $amount = $creditCard->balance;
        }

        if($creditCard->type == EntityWallet::creditCardRevolving->value) {
            if($creditCard->installement_value < $creditCard->balance ) {
                $amount = $creditCard->balance;
            }
        }

        //create new payment
        $entry = new Entry();
        $entry->uuid = Uuid::uuid4();
        $entry->date_time = Carbon::now()->format(Format::dateTime->value);
        $entry->account_id = $creditCard->payment_account;
        $entry->amount = $amount;
        $entry->note = $creditCard->name;
        $entry->planned = false;
        $entry->confirmed = true;
        $entry->currency_id = $creditCard->currency;
        $entry->payment_type = 2;
        $entry->category_id = 75;
        $entry->type = EntityEntry::transfer->value;
        $entry->transfer_id = $creditCard->id;
        $entry->workspace_id = $creditCard->workspace_id;
        $entry->transfer = true;
        $entry->save();

        Log::debug('Entry created', ['entry' => $entry->toArray()]);

        return $entry;
    }

    private function conditions(Wallet $creditCard): bool
    {

        if($creditCard->balance >= 0) {
            return false;
        }

        return true;
    }

}
