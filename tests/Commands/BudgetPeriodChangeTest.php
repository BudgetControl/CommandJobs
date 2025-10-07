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
        $this->assertTrue($this->application->has('budget:period-change'));
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->application->find('budget:period-change');
        
        $this->assertEquals('budget:period-change', $command->getName());
        $this->assertEquals('Change budget period', $command->getDescription());
        $this->assertNotEmpty($command->getHelp());
    }

    public function testCommandExecutesSuccessfully(): void
    {
        $exitCode = $this->executeCommand('budget:period-change');
        
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Job completed', $this->getDisplay());
    }
}
