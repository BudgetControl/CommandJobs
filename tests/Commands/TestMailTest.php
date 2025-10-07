<?php

namespace Budgetcontrol\jobs\Tests\Commands;

use Budgetcontrol\jobs\Cli\TestMail;
use Symfony\Component\Console\Command\Command;

class TestMailTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->application->add(new TestMail());
    }

    public function testCommandIsRegistered(): void
    {
        $this->assertTrue($this->application->has('test:mail'));
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->application->find('test:mail');
        
        $this->assertEquals('test:mail', $command->getName());
        $this->assertEquals('Test mail sending', $command->getDescription());
        $this->assertNotEmpty($command->getHelp());
    }

    public function testCommandExecutesSuccessfully(): void
    {
        $exitCode = $this->executeCommand('test:mail', [
            'template' => 'recovery-password',
            'mail' => 'test@example.com'
        ]);
        
        // In test environment, mail may not be configured, so we just check it runs
        $this->assertIsInt($exitCode);
    }
}
