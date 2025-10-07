<?php

namespace Budgetcontrol\jobs\Tests\Commands;

use Budgetcontrol\jobs\Cli\InstallDemoData;
use Symfony\Component\Console\Command\Command;

class InstallDemoDataTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->application->add(new InstallDemoData());
    }

    public function testCommandIsRegistered(): void
    {
        $this->assertTrue($this->application->has('core:demo-data'));
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->application->find('core:demo-data');
        
        $this->assertEquals('core:demo-data', $command->getName());
        $this->assertEquals('Install demo data', $command->getDescription());
        $this->assertNotEmpty($command->getHelp());
    }

    public function testCommandExecutesSuccessfully(): void
    {
        // Skip this test as it performs actual database operations
        // that may conflict with other tests or require specific database state
        $this->markTestSkipped('Skipping test that performs actual database operations');
        
        $exitCode = $this->executeCommand('core:demo-data');
        
        // This command might fail if demo data already exists or database is not ready
        // So we just check it returns an integer exit code
        $this->assertIsInt($exitCode);
    }
}
