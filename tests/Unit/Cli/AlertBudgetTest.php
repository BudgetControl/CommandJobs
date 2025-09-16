<?php

namespace Tests\Unit\Cli;

use Tests\TestCase;
use Mockery;
use Budgetcontrol\jobs\Cli\AlertBudget;
use Budgetcontrol\Library\Model\Budget;
use Budgetcontrol\Library\Model\Workspace;
use Budgetcontrol\jobs\Service\NotificationService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Support\Facades\Log;

class AlertBudgetTest extends TestCase
{
    private $command;
    private $mockInput;
    private $mockOutput;
    private $mockNotificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new AlertBudget();
        $this->mockInput = Mockery::mock(InputInterface::class);
        $this->mockOutput = Mockery::mock(OutputInterface::class);
        $this->mockNotificationService = Mockery::mock(NotificationService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testExecuteWithNoBudgets()
    {
        Budget::shouldReceive('where')->andReturn((object)[
            'where' => function() { return (object)['get' => function() { return collect([]); }]; }
        ]);

        Log::shouldReceive('info')->with('Check budget alerts');
        
        $result = $this->command->execute($this->mockInput, $this->mockOutput);
        $this->assertEquals(0, $result);
    }

    public function testExecuteWithBudgets()
    {
        $mockBudget = Mockery::mock(Budget::class);
        $mockBudget->workspace_id = 1;
        $mockBudget->amount = 1000;
        $mockBudget->current_amount = 900;
        $mockBudget->name = 'Test Budget';
        $mockBudget->shouldReceive('getAttribute')->with('percentage')->andReturn(90);

        $mockWorkspace = Mockery::mock(Workspace::class);
        $mockWorkspace->users = collect([
            (object)['uuid' => 'test-uuid']
        ]);

        Budget::shouldReceive('where')->andReturn((object)[
            'where' => function() use ($mockBudget) { 
                return (object)['get' => function() use ($mockBudget) { 
                    return collect([$mockBudget]); 
                }]; 
            }
        ]);

        Workspace::shouldReceive('with')->with('users')
            ->andReturn((object)['find' => function() use ($mockWorkspace) { 
                return $mockWorkspace; 
            }]);

        $this->mockNotificationService->shouldReceive('sendPushNotificationToUser')
            ->once()
            ->with('test-uuid', 'Budget Alert', Mockery::pattern('/Budget Test Budget has reached/'));

        Log::shouldReceive('info')->with('Check budget alerts');
        
        $result = $this->command->execute($this->mockInput, $this->mockOutput);
        $this->assertEquals(0, $result);
    }
}