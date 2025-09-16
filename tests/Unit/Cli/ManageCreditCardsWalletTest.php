<?php

namespace Tests\Unit\Cli;

use Tests\TestCase;
use Mockery;
use Budgetcontrol\jobs\Cli\ManageCreditCardsWallet;
use Budgetcontrol\Library\Model\Wallet;
use Budgetcontrol\Library\Model\Entry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ManageCreditCardsWalletTest extends TestCase
{
    private $command;
    private $mockInput;
    private $mockOutput;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new ManageCreditCardsWallet();
        $this->mockInput = Mockery::mock(InputInterface::class);
        $this->mockOutput = Mockery::mock(OutputInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testExecuteWithNoWallets()
    {
        Wallet::shouldReceive('where')->with('type', 'credit_card')
            ->andReturn((object)['get' => function() { return collect([]); }]);

        Log::shouldReceive('info')->with('Check credit cards wallets');
        
        $result = $this->command->execute($this->mockInput, $this->mockOutput);
        $this->assertEquals(0, $result);
    }

    public function testExecuteWithWallets()
    {
        $mockWallet = Mockery::mock(Wallet::class);
        $mockWallet->id = 1;
        $mockWallet->workspace_id = 1;
        $mockWallet->amount = 1000;
        $mockWallet->name = 'Test Credit Card';

        $mockEntry = Mockery::mock(Entry::class);
        $mockEntry->amount = 500;
        $mockEntry->type = 'credit';
        
        Wallet::shouldReceive('where')->with('type', 'credit_card')
            ->andReturn((object)['get' => function() use ($mockWallet) { 
                return collect([$mockWallet]); 
            }]);

        Entry::shouldReceive('where')->with('wallet_id', 1)
            ->andReturn((object)[
                'whereMonth' => function() use ($mockEntry) { 
                    return (object)['get' => function() use ($mockEntry) { 
                        return collect([$mockEntry]); 
                    }]; 
                }
            ]);

        Log::shouldReceive('info')->with('Check credit cards wallets');
        Log::shouldReceive('info')->with(Mockery::pattern('/Processing credit card wallet/'));
        
        $result = $this->command->execute($this->mockInput, $this->mockOutput);
        $this->assertEquals(0, $result);
    }
}