<?php

namespace Budgetcontrol\jobs\Tests\Commands;

use Budgetcontrol\jobs\Cli\AlertBudgetNotification;
use Symfony\Component\Console\Command\Command;

class AlertBudgetNotificationTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->application->add(new AlertBudgetNotification());
    }

    public function testCommandIsRegistered(): void
    {
        $this->assertTrue($this->application->has('budget:notify-threshold'));
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->application->find('budget:notify-threshold');
        
        $this->assertEquals('budget:notify-threshold', $command->getName());
        $this->assertEquals('Send push notifications for budget thresholds', $command->getDescription());
        $this->assertNotEmpty($command->getHelp());
    }

    public function testCommandExecutesSuccessfully(): void
    {
        $exitCode = $this->executeCommand('budget:notify-threshold');
        
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Job completed', $this->getDisplay());
    }

    public function testCommandHandlesWorkspacesWithoutBudgets(): void
    {
        $exitCode = $this->executeCommand('budget:notify-threshold');
        
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }
}
