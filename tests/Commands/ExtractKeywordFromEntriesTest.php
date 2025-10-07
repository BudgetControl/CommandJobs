<?php

namespace Budgetcontrol\jobs\Tests\Commands;

use Budgetcontrol\jobs\Cli\ExtractKeywordFromEntries;
use Symfony\Component\Console\Command\Command;

class ExtractKeywordFromEntriesTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->application->add(new ExtractKeywordFromEntries());
    }

    public function testCommandIsRegistered(): void
    {
        $this->assertTrue($this->application->has('core:extract-keywords'));
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->application->find('core:extract-keywords');
        
        $this->assertEquals('core:extract-keywords', $command->getName());
        $this->assertEquals('Extract keywords from entries', $command->getDescription());
        $this->assertNotEmpty($command->getHelp());
    }

    public function testCommandExecutesSuccessfully(): void
    {
        $exitCode = $this->executeCommand('core:extract-keywords');
        
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Job completed', $this->getDisplay());
    }
}
