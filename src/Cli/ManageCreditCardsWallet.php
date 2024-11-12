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

        $creditCards = Wallet::whereIn('type', [EntityWallet::creditCard->value, EntityWallet::creditCardRevolving->value])
        ->where("deletedAt", null)
        ->get();

        try {

            foreach ($creditCards as $creditCard) {
                $this->manageCreditCard($creditCard);
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
            $this->createDebitNegativeEntry($creditCard);
            $this->createDebitPositiveEntry($creditCard);
        }

        $this->changeWalletDate($creditCard);
    }

    /**
     * Creates a debit negative entry for the given wallet.
     *
     * @param Wallet $wallet The wallet for which the debit negative entry will be created.
     *
     * @return void
     */
    private function createDebitNegativeEntry(Wallet $wallet): void
    {
        $entry = $this->createDebitEntry($wallet);
        $entry->account_id = $wallet->payment_account;
        $entry->amount = $wallet->installement_value * -1; // shoulde be negative
        $entry->save();

        $this->updateWalletBalance($wallet->payment_account, $wallet->installement_value * -1);

        Log::debug("Save new debit entry [NEGATIVE] ".json_encode($entry->toArray()));
    }

    /**
     * Creates a positive debit entry in the specified wallet.
     *
     * @param Wallet $wallet The wallet in which to create the debit entry.
     *
     * @return void
     */
    private function createDebitPositiveEntry(Wallet $wallet): void
    {
        $entry = $this->createDebitEntry($wallet);
        $entry->account_id = $wallet->id;
        $entry->amount = $wallet->installement_value;
        $entry->save();

        $this->updateWalletBalance($wallet->id, $wallet->installement_value);

        Log::debug("Save new debit entry [POSITIVE] ".json_encode($entry->toArray()));

    }

    /**
     * Creates a debit entry for the given wallet.
     *
     * @param Wallet $wallet The wallet for which the debit entry is to be created.
     * @return Debit The created debit entry.
     */
    private function createDebitEntry(Wallet $wallet): Debit
    {
        $transactionUUID = Uuid::uuid4();

        $entry = new Debit();
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
        $entry->transfer = false;
        $entry->payee_id = 0; //FIXME:

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
        $wallet = Wallet::firstOrFail($walletId);
        $newWalletBalance = $wallet->balance - $amount;
        $wallet->balacne = $newWalletBalance;
        $wallet->save();

        Log::debug("New Wallet balance for {$wallet->id} [$newWalletBalance]");
    }

}
