<?php

namespace Tests\Unit\Cli;

use Tests\TestCase;
use Mockery;
use Budgetcontrol\jobs\Cli\ExtractKeywordFromEntries;
use Budgetcontrol\Library\Model\Entry;
use Budgetcontrol\Library\Model\EntryKeywords;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Support\Facades\Log;

class ExtractKeywordFromEntriesTest extends TestCase
{
    private $command;
    private $mockInput;
    private $mockOutput;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new ExtractKeywordFromEntries();
        $this->mockInput = Mockery::mock(InputInterface::class);
        $this->mockOutput = Mockery::mock(OutputInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testExecuteWithNoEntries()
    {
        Entry::shouldReceive('where')->with('has_keywords', '=', false)
            ->once()
            ->andReturn((object)['chunk' => function($size, $callback) { return []; }]);

        Log::shouldReceive('info')->with('Keywords extraction completed.');

        $result = $this->command->execute($this->mockInput, $this->mockOutput);
        $this->assertEquals(0, $result);
    }

    public function testExecuteWithEntries()
    {
        $mockEntry = Mockery::mock(Entry::class);
        $mockEntry->id = 1;
        $mockEntry->note = 'Test note for keyword extraction';
        $mockEntry->has_keywords = false;

        Entry::shouldReceive('where')->with('has_keywords', '=', false)
            ->once()
            ->andReturn((object)[
                'chunk' => function($size, $callback) use ($mockEntry) { 
                    $callback([$mockEntry]); 
                    return true;
                }
            ]);

        EntryKeywords::shouldReceive('where')->with('entry_id', 1)
            ->andReturn((object)['delete' => function() { return true; }]);

        EntryKeywords::shouldReceive('create')->times(3);

        Log::shouldReceive('info')->with('Keywords extraction completed.');
        Log::shouldReceive('info')->with(Mockery::pattern('/Entry ID \d+ keywords extracted and saved./'));

        $result = $this->command->execute($this->mockInput, $this->mockOutput);
        $this->assertEquals(0, $result);
    }

    public function testChunkSize()
    {
        $command = new ExtractKeywordFromEntries();
        $this->assertEquals(250, $command->getChunkSize());
    }
}