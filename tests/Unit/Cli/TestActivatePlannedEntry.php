<?php

namespace Tests\Unit\Cli;

use Budgetcontrol\jobs\Cli\ActivatePlannedEntry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestActivatePlannedEntry extends ActivatePlannedEntry
{
    public function executeTest(InputInterface $input, OutputInterface $output): int
    {
        return $this->execute($input, $output);
    }

    public function findUserUuidByWorkspaceIdTest(int $workspaceId): array
    {
        return $this->findUserUuidByWorkspaceId($workspaceId);
    }

    public function sendUserNotificationTest(array $userUuids, string $title, string $body): void
    {
        $this->sendUserNotification($userUuids, $title, $body);
    }
}