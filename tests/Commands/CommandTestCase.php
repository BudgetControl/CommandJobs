<?php

namespace Budgetcontrol\jobs\Tests\Commands;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Illuminate\Support\Facades\Log;
use Budgetcontrol\Library\Model\Workspace;
use Budgetcontrol\Library\Model\User;

abstract class CommandTestCase extends TestCase
{
    protected Application $application;
    protected CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->application = new Application();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Execute a command with the given command name and parameters
     *
     * @param string $commandName
     * @param array $parameters
     * @return int
     */
    protected function executeCommand(string $commandName, array $parameters = []): int
    {
        $command = $this->application->find($commandName);
        $this->commandTester = new CommandTester($command);
        
        return $this->commandTester->execute($parameters);
    }

    /**
     * Get the display output from the last command execution
     *
     * @return string
     */
    protected function getDisplay(): string
    {
        return $this->commandTester->getDisplay();
    }

    /**
     * Assert command executed successfully
     *
     * @return void
     */
    protected function assertCommandIsSuccessful(): void
    {
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * Assert command failed
     *
     * @return void
     */
    protected function assertCommandFailed(): void
    {
        $this->assertNotEquals(0, $this->commandTester->getStatusCode());
    }
}
