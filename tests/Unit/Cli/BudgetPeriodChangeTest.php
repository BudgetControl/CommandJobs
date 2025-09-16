<?php

namespace Tests\Unit\Cli;

use Tests\TestCase;
use Mockery;
use Budgetcontrol\jobs\Cli\BudgetPeriodChange;
use Budgetcontrol\Library\Model\Budget;
use Budgetcontrol\Library\Model\BudgetPeriod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BudgetPeriodChangeTest extends TestCase
{
    private $command;
    private $mockInput;
    private $mockOutput;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new BudgetPeriodChange();
        $this->mockInput = Mockery::mock(InputInterface::class);
        $this->mockOutput = Mockery::mock(OutputInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testExecuteWithNoBudgets()
    {
        Budget::shouldReceive('where')->andReturn((object)[
            'get' => function() { return collect([]); }
        ]);

        Log::shouldReceive('info')->with('Check budget period change');
        
        $result = $this->command->execute($this->mockInput, $this->mockOutput);
        $this->assertEquals(0, $result);
    }

    public function testExecuteWithBudgets()
    {
        $mockBudget = Mockery::mock(Budget::class);
        $mockBudget->id = 1;
        $mockBudget->workspace_id = 1;
        $mockBudget->amount = 1000;
        $mockBudget->current_amount = 0;

        $mockPeriod = Mockery::mock(BudgetPeriod::class);
        $mockPeriod->shouldReceive('getAttribute')->with('end_date')
            ->andReturn(Carbon::now()->subDay());

        Budget::shouldReceive('where')->andReturn((object)[
            'get' => function() use ($mockBudget) { return collect([$mockBudget]); }
        ]);

        BudgetPeriod::shouldReceive('where')->with('budget_id', 1)
            ->andReturn((object)[
                'orderBy' => function() use ($mockPeriod) { 
                    return (object)['first' => function() use ($mockPeriod) { 
                        return $mockPeriod; 
                    }]; 
                }
            ]);

        Log::shouldReceive('info')->with('Check budget period change');
        Log::shouldReceive('info')->with(Mockery::pattern('/Budget period changed for budget/'));
        
        $result = $this->command->execute($this->mockInput, $this->mockOutput);
        $this->assertEquals(0, $result);
    }
}