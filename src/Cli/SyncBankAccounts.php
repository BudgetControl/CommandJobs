<?php
declare(strict_types=1);

namespace Budgetcontrol\jobs\Cli;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SyncBankAccounts extends JobCommand
{
    protected string $command = 'bank:sync';

    protected function configure(): void
    {
        $this->setName($this->command)->setDescription('Synchronize configured bank accounts');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $base = rtrim((string) env('SYNC_API_BASE_URL', ''), '/');
        $apiSecret = (string) env('API_SECRET', '');
        $jobSecret = (string) env('SYNC_JOB_SECRET', '');
        if ($base === '' || $apiSecret === '' || $jobSecret === '') {
            $output->writeln('<error>Sync job is not configured.</error>');
            return Command::FAILURE;
        }
        try {
            $response = (new Client(['timeout' => 900, 'connect_timeout' => 10, 'http_errors' => false]))
                ->post($base . '/internal/scheduled-sync', ['headers' => [
                    'X-API-SECRET' => $apiSecret,
                    'X-Sync-Job-Secret' => $jobSecret,
                ]]);
            $result = json_decode((string) $response->getBody(), true);
            foreach (($result['workspace_ids'] ?? []) as $workspaceId) {
                $this->invokeClearCache('entry', (int) $workspaceId);
                $this->invokeClearCache('label', (int) $workspaceId);
            }
            if ($response->getStatusCode() !== 200 || !is_array($result) || !isset($result['processed'], $result['failed'])) {
                $output->writeln('<error>Bank synchronization failed; check Sync service logs.</error>');
                return Command::FAILURE;
            }
            $output->writeln(sprintf('Bank accounts synchronized: %d; failed: %d.', $result['processed'], $result['failed']));
            return (int) $result['failed'] === 0 ? Command::SUCCESS : Command::FAILURE;
        } catch (\Throwable $e) {
            $output->writeln('<error>Sync service unavailable.</error>');
            return Command::FAILURE;
        }
    }
}
