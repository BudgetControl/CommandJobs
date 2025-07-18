<?php
declare(strict_types=1);

namespace Budgetcontrol\jobs\Cli;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Webit\Wrapper\BcMath\BcMathNumber;
use Budgetcontrol\Library\Definition\Format;
use Budgetcontrol\Library\Entity\Entry as EntityEntry;
use Budgetcontrol\Library\Entity\Wallet as EntityWallet;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Budgetcontrol\Library\Model\Debit;
use Budgetcontrol\Library\Model\Transfer;
use Budgetcontrol\Library\Model\Wallet; 

/**
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
        $this->output = $output;

        $creditCards = Wallet::whereIn('type', [EntityWallet::creditCard->value, EntityWallet::creditCardRevolving->value])
        ->where('invoice_date', '<=', Carbon::now()->format(Format::dateTime->value))
        ->where("deleted_at", null)
        ->get();

        try {

            foreach ($creditCards as $creditCard) {
                $this->manageCreditCard($creditCard);
                $this->invokeClearCache('wallet', $creditCard->workspace_id);
            }

        } catch (\Throwable $e) {
            $this->fail($e->getMessage());
            return Command::FAILURE;
        }

        $this->heartbeats(env('HEARTBEAT_MANAGE_CREDIT_CARDS'));
        Log::info('Credit cards managed successfully');
        return Command::SUCCESS;
    }

    private function manageCreditCard(Wallet $creditCard)
    {
        //first check if the balance is greather then 0
        if($creditCard->balance < 0) {
            // you need to create new debit entry
            $negativEntry = $this->createTransferNegativeEntry($creditCard);
            $positiveEntry = $this->createTransferPositiveEntry($creditCard);
            $this->updateTransferRelastionField($negativEntry, $positiveEntry);
        }

        $this->changeWalletDate($creditCard);
    }

    /**
     * Creates a debit negative entry for the given wallet.
     *
     * @param Wallet $wallet The wallet for which the debit negative entry will be created.
     *
     * @return Transfer
     */
    protected function createTransferNegativeEntry(Wallet $wallet): Transfer
    {
        $amount = function() use($wallet): float {
            $value = $wallet->installement_value * -1;
            // if is not revolving card return the balance
            if($wallet->type === EntityWallet::creditCard->value) {
                return $wallet->balance * -1;
            }
            return (float) $value >= $wallet->balance ? $wallet->installement_value : $wallet->balance * -1;
        };

        $entry = $this->createTransferEntry($wallet);
        $entry->account_id = $wallet->payment_account;
        $entry->amount = $amount(); // shoulde be negative
        $entry->save();

        $this->updateWalletBalance($wallet->payment_account, $amount());

        Log::debug("Save new debit entry [NEGATIVE] ".json_encode($entry->toArray()));

        return $entry;
    }

    /**
     * Creates a positive debit entry in the specified wallet.
     *
     * @param Wallet $wallet The wallet in which to create the debit entry.
     * @param string|null $transferRelation Optional transfer relation, if any.
     *
     * @return Transfer The created debit entry.
     */
    protected function createTransferPositiveEntry(Wallet $wallet): Transfer
    {
        $amount = function()use($wallet) {
            $value = $wallet->installement_value * -1;
            return $value >= $wallet->balance ? $wallet->installement_value : $wallet->balance  * -1;
        };

        $entry = $this->createTransferEntry($wallet);
        $entry->account_id = $wallet->id;
        $entry->amount = $amount() * -1;
        $entry->save();

        $this->updateWalletBalance($wallet->id, $amount() * -1);

        Log::debug("Save new debit entry [POSITIVE] ".json_encode($entry->toArray()));

        return $entry;

    }

     /**
     * Updates the relation field between two transfer entries.
     *
     * Associates a negative transfer entry with a positive one, typically
     * used when handling credit card wallet transfers.
     *
     * @param Transfer $entryNegative The negative transfer entry
     * @param Transfer $entryPositive The positive transfer entry
     * @return void
     */
    protected function updateTransferRelastionField(Transfer $entryNegative, Transfer $entryPositive): void
    {
        // update the transfer relation field
        $entryNegative->transfer_relation = $entryPositive->uuid;
        $entryNegative->save();

        $entryPositive->transfer_relation = $entryNegative->uuid;
        $entryPositive->save();

        Log::debug("Update transfer relation for [NEGATIVE] entry {$entryNegative->uuid} to {$entryPositive->uuid}");
    }

    /**
     * Creates a debit entry for the given wallet.
     *
     * @param Wallet $wallet The wallet for which the debit entry is to be created.
     * @return Transfer The created debit entry.
     */
    private function createTransferEntry(Wallet $wallet): Transfer
    {
        $transactionUUID = Uuid::uuid4();

        $entry = new Transfer();
        $entry->uuid = $transactionUUID;
        $entry->date_time = Carbon::now()->format(Format::dateTime->value);
        $entry->note = 'Credit card payment: '.$transactionUUID;
        $entry->planned = false;
        $entry->confirmed = true;
        $entry->currency_id = $wallet->currency;
        $entry->payment_type = 2; //FIXME:
        $entry->category_id = 75; //FIXME:
        // $entry->type = EntityEntry::debit->value; FIXME:
        $entry->workspace_id = $wallet->workspace_id;
        $entry->transfer = true;

        return $entry;

    }

    /**
     * Changes the date of the given wallet.
     *
     * @param Wallet $wallet The wallet whose date needs to be changed.
     *
     * @return void
     */
    private function changeWalletDate(Wallet $wallet): void
    {
        // move date to next month
        $newInvoiceDate = Carbon::parse($wallet->invoice_date)->addMonth();
        $newClosingDate = Carbon::parse($wallet->closing_date)->addMonth();

        $wallet->invoice_date = $newInvoiceDate->format(Format::date->value . ' 00:00:00');
        $wallet->closing_date = $newClosingDate->format(Format::date->value . ' 00:00:00');
        $wallet->save();

        log::debug('Credit card updated', ['creditCard' => $wallet->toArray()]);
    }

    /**
     * Updates the balance of a specified wallet.
     *
     * @param int $walletId The ID of the wallet to update.
     * @param float|int $balance The new amount to set for the wallet.
     */
    private function updateWalletBalance(int $walletId, float|int $amount)
    {
        $wallet = Wallet::where('id', $walletId)->first();
        $newWalletBalance = $wallet->balance - $amount;
        $wallet->balance = $newWalletBalance;
        $wallet->save();

        Log::debug("New Wallet balance for {$wallet->id} [$newWalletBalance]");
    }

}
