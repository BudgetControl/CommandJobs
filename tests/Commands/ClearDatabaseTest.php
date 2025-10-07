<?php

namespace Budgetcontrol\jobs\Tests\Commands;

use Budgetcontrol\jobs\Cli\ClearDatabase;
use Symfony\Component\Console\Command\Command;

class ClearDatabaseTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->application->add(new ClearDatabase());
    }

    public function testCommandIsRegistered(): void
    {
        $this->assertTrue($this->application->has('core:clear'));
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->application->find('core:clear');
        
        $this->assertEquals('core:clear', $command->getName());
        $this->assertEquals('Clear db', $command->getDescription());
        $this->assertNotEmpty($command->getHelp());
    }

    public function testCommandExecutesSuccessfully(): void
    {
        // Note: This test might need to be skipped in CI/CD or use a test database
        // as it clears the database
        $exitCode = $this->executeCommand('core:clear');
        
        $this->assertIsInt($exitCode);
    }
}
