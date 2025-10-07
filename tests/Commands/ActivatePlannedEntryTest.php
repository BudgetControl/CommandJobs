<?php

namespace Budgetcontrol\jobs\Tests\Commands;

use Budgetcontrol\jobs\Cli\ActivatePlannedEntry;
use Budgetcontrol\jobs\Domain\Model\Entry;
use Symfony\Component\Console\Command\Command;

class ActivatePlannedEntryTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->application->add(new ActivatePlannedEntry());
    }

    public function testCommandIsRegistered(): void
    {
        $this->assertTrue($this->application->has('entry:activate-planned'));
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->application->find('entry:activate-planned');
        
        $this->assertEquals('entry:activate-planned', $command->getName());
        $this->assertEquals('Activate a planned entry', $command->getDescription());
        $this->assertNotEmpty($command->getHelp());
    }

    public function testCommandExecutesSuccessfully(): void
    {
        $exitCode = $this->executeCommand('entry:activate-planned');
        
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Job completed', $this->getDisplay());
    }

    public function testCommandLogsInfo(): void
    {
        $exitCode = $this->executeCommand('entry:activate-planned');
        
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }
}
