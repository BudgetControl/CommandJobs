<?php

namespace Budgetcontrol\jobs\Tests\Commands;

use Budgetcontrol\jobs\Cli\ManageCreditCardsWallet;
use Symfony\Component\Console\Command\Command;

class ManageCreditCardsWalletTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->application->add(new ManageCreditCardsWallet());
    }

    public function testCommandIsRegistered(): void
    {
        $this->assertTrue($this->application->has('wallet:update-credit-card'));
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->application->find('wallet:update-credit-card');
        
        $this->assertEquals('wallet:update-credit-card', $command->getName());
        $this->assertEquals('Manage credit cards in the wallet', $command->getDescription());
        $this->assertNotEmpty($command->getHelp());
    }

    public function testCommandExecutesSuccessfully(): void
    {
        $exitCode = $this->executeCommand('wallet:update-credit-card');
        
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Job completed', $this->getDisplay());
    }
}
