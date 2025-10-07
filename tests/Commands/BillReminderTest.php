<?php

namespace Budgetcontrol\jobs\Tests\Commands;

use Budgetcontrol\jobs\Cli\BillReminder;
use Symfony\Component\Console\Command\Command;

class BillReminderTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->application->add(new BillReminder());
    }

    public function testCommandIsRegistered(): void
    {
        $this->assertTrue($this->application->has('entry:check-bill-reminder'));
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->application->find('entry:check-bill-reminder');
        
        $this->assertEquals('entry:check-bill-reminder', $command->getName());
        $this->assertEquals('Check bill reminders', $command->getDescription());
        $this->assertNotEmpty($command->getHelp());
    }

    public function testCommandHasDaysOption(): void
    {
        $command = $this->application->find('entry:check-bill-reminder');
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasOption('days'));
        $this->assertEquals(2, $definition->getOption('days')->getDefault());
    }

    public function testCommandExecutesWithDefaultDays(): void
    {
        $exitCode = $this->executeCommand('entry:check-bill-reminder');
        
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Job completed', $this->getDisplay());
    }

    public function testCommandExecutesWithCustomDays(): void
    {
        $exitCode = $this->executeCommand('entry:check-bill-reminder', [
            '--days' => 5
        ]);
        
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Job completed', $this->getDisplay());
    }
}
