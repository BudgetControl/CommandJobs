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
        $this->assertTrue($this->application->has('wallet:manage-credit-cards'));
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->application->find('wallet:manage-credit-cards');
        
        $this->assertEquals('wallet:manage-credit-cards', $command->getName());
        $this->assertEquals('Manage credit cards wallet', $command->getDescription());
        $this->assertNotEmpty($command->getHelp());
    }

    public function testCommandExecutesSuccessfully(): void
    {
        $exitCode = $this->executeCommand('wallet:manage-credit-cards');
        
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Job completed', $this->getDisplay());
    }
}
