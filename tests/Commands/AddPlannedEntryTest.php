<?php

namespace Budgetcontrol\jobs\Tests\Commands;

use Budgetcontrol\jobs\Cli\AddPlannedEntry;
use Symfony\Component\Console\Command\Command;

class AddPlannedEntryTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->application->add(new AddPlannedEntry());
    }

    public function testCommandIsRegistered(): void
    {
        $this->assertTrue($this->application->has('entry:add-planned'));
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->application->find('entry:add-planned');
        
        $this->assertEquals('entry:add-planned', $command->getName());
        $this->assertEquals('Add planned entry', $command->getDescription());
        $this->assertNotEmpty($command->getHelp());
    }

    public function testCommandExecutesSuccessfully(): void
    {
        $exitCode = $this->executeCommand('entry:add-planned');
        
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Job completed', $this->getDisplay());
    }
}
