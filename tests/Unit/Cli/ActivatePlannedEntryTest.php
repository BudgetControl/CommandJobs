<?php

namespace Tests\Unit\Cli;

use Tests\TestCase;
use Budgetcontrol\jobs\Cli\ActivatePlannedEntry;
use Budgetcontrol\jobs\Domain\Repository\EntryRepository;
use Budgetcontrol\jobs\Service\NotificationService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Budgetcontrol\Library\Model\Entry;
use Budgetcontrol\Library\Model\Workspace;
use Illuminate\Support\Facades\Log;
use Mockery;

class ActivatePlannedEntryTest extends TestCase
{
    private $command;
    private $mockInput;
    private $mockOutput;
    private $mockRepository;
    private $mockNotificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockInput = Mockery::mock(InputInterface::class);
        $this->mockOutput = Mockery::mock(OutputInterface::class);
        $this->mockRepository = Mockery::mock(EntryRepository::class);
        $this->mockNotificationService = Mockery::mock(NotificationService::class);
        
        $this->command = new TestActivatePlannedEntry();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testExecuteWithNoEntries()
    {
        $this->mockRepository->shouldReceive('entryOfCurrentTime')
            ->once()
            ->andReturn(null);


        $result = $this->command->executeTest($this->mockInput, $this->mockOutput);
        
        $this->assertEquals(0, $result);
    }

    public function testExecuteWithEntries()
    {
        $mockEntry = Mockery::mock(Entry::class);
        $mockEntry->id = 1;
        $mockEntry->workspace_id = 1;
        $mockEntry->note = 'Test Entry';
        $mockEntry->amount = 100;

        $this->mockRepository->shouldReceive('entryOfCurrentTime')
            ->once()
            ->andReturn([$mockEntry]);

        $mockWorkspace = Mockery::mock(Workspace::class);
        $mockWorkspace->shouldReceive('users->pluck')
            ->andReturn(collect(['test-uuid']));

        Workspace::shouldReceive('with')
            ->with('users')
            ->andReturn($mockWorkspace);

        $this->mockNotificationService->shouldReceive('sendPushNotificationToUser')
            ->once()
            ->with('test-uuid', 'Nuova spesa pianificata', 'La spesa "Test Entry" è stata attivata. 100€');

        $result = $this->command->executeTest($this->mockInput, $this->mockOutput);
        
        $this->assertEquals(0, $result);
    }

    public function testFindUserUuidByWorkspaceId()
    {
        $workspaceId = 1;
        $expectedUuids = ['uuid-1', 'uuid-2'];

        $mockWorkspace = Mockery::mock(Workspace::class);
        $mockWorkspace->shouldReceive('users->pluck')
            ->with('uuid')
            ->andReturn(collect($expectedUuids));

        Workspace::shouldReceive('with')
            ->with('users')
            ->andReturn($mockWorkspace);

        $result = $this->command->findUserUuidByWorkspaceIdTest($workspaceId);
        
        $this->assertEquals($expectedUuids, $result);
    }

    public function testSendUserNotification()
    {
        $userUuids = ['uuid-1', 'uuid-2'];
        $title = 'Test Title';
        $body = 'Test Body';

        $this->mockNotificationService->shouldReceive('sendPushNotificationToUser')
            ->twice()
            ->withArgs(function ($uuid, $testTitle, $testBody) use ($title, $body) {
                return in_array($uuid, ['uuid-1', 'uuid-2']) &&
                       $testTitle === $title &&
                       $testBody === $body;
            });

        $this->command->sendUserNotificationTest($userUuids, $title, $body);
    }
}