<?php

namespace Budgetcontrol\jobs\Tests\Commands;

use Budgetcontrol\jobs\Cli\BudgetPeriodChange;
use Symfony\Component\Console\Command\Command;

class BudgetPeriodChangeTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->application->add(new BudgetPeriodChange());
    }

    public function testCommandIsRegistered(): void
    {
        $this->assertTrue($this->application->has('budget:is-expired'));
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->application->find('budget:is-expired');
        
        $this->assertEquals('budget:is-expired', $command->getName());
        $this->assertEquals('Change budget period only for recursive budgets', $command->getDescription());
        $this->assertNotEmpty($command->getHelp());
    }

    public function testCommandExecutesSuccessfully(): void
    {
        $exitCode = $this->executeCommand('budget:is-expired');
        
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Job completed', $this->getDisplay());
    }
}
