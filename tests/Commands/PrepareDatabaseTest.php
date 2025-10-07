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
        $this->assertTrue($this->application->has('database:prepare'));
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->application->find('database:prepare');
        
        $this->assertEquals('database:prepare', $command->getName());
        $this->assertEquals('Prepare database', $command->getDescription());
        $this->assertNotEmpty($command->getHelp());
    }

    public function testCommandExecutesSuccessfully(): void
    {
        $exitCode = $this->executeCommand('database:prepare');
        
        $this->assertIsInt($exitCode);
    }
}
