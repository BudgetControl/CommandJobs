<?php

namespace Budgetcontrol\jobs\Tests\Commands;

use Budgetcontrol\jobs\Cli\AlertBudget;
use Symfony\Component\Console\Command\Command;

class AlertBudgetTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->application->add(new AlertBudget());
    }

    public function testCommandIsRegistered(): void
    {
        $this->assertTrue($this->application->has('budget:email-exceeded'));
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->application->find('budget:email-exceeded');
        
        $this->assertEquals('budget:email-exceeded', $command->getName());
        $this->assertEquals('Check if budget is exceeded', $command->getDescription());
        $this->assertNotEmpty($command->getHelp());
    }

    public function testCommandExecutesSuccessfully(): void
    {
        $exitCode = $this->executeCommand('budget:email-exceeded');
        
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Job completed', $this->getDisplay());
    }
}
