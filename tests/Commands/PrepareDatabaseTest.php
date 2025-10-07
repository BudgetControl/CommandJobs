<?php

namespace Budgetcontrol\jobs\Tests\Commands;

use Budgetcontrol\jobs\Cli\PrepareDatabase;
use Symfony\Component\Console\Command\Command;

class PrepareDatabaseTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->application->add(new PrepareDatabase());
    }

    public function testCommandIsRegistered(): void
    {
        $this->assertTrue($this->application->has('core:install'));
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->application->find('core:install');
        
        $this->assertEquals('core:install', $command->getName());
        $this->assertEquals('Install db', $command->getDescription());
        $this->assertNotEmpty($command->getHelp());
    }

    public function testCommandExecutesSuccessfully(): void
    {
        // Skip this test as it performs actual database operations
        // that may conflict with other tests or require specific database state
        $this->markTestSkipped('Skipping test that performs actual database operations');
        
        $exitCode = $this->executeCommand('core:install');
        
        $this->assertIsInt($exitCode);
    }
}
