<?php
namespace Budgetcontrol\jobs\Cli;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Budgetcontrol\Library\Model\Workspace;
use Budgetcontrol\Library\Definition\Format;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

abstract class JobCommand extends Command
{
    const JOBS_TABLE = 'failed_jobs';
    protected string $command;
    protected OutputInterface $output;

    protected function fail(string $exception): void
    {
        Log::error('Job failed '.$exception);
        $query = "INSERT INTO ".self::JOBS_TABLE." (uuid, command, exception, failed_at) VALUES 
        ('". Uuid::uuid4() ."', '".$this->command."', '".addslashes($exception)."', '".Carbon::now()->format(Format::dateTime->value)."')";
        Db::insert($query);
        $this->output->writeln("Job failed - check error logs");
    }

    protected function heartbeats(string|null $key): void
    {
        if($key) {
            $http = new \GuzzleHttp\Client();
            $http->head('https://uptime.betterstack.com/api/v1/heartbeat/'.$key);
        }

        $this->output->writeln("Job completed");
    }

    /**
     * Invokes a clear cache operation with the given pattern.
     *
     * This method is responsible for clearing the cache entries matching the specified pattern.
     *
     * @param string $pattern The pattern used to identify which cache entries should be cleared.
     * @param int $workspaceId The id of the workspace for which the cache should be cleared.
     * @return void
     */
    protected function invokeClearCache(string $pattern, ?int $workspaceId = null): void
    {
        if($workspaceId !== null) {
            $workspaceUuid = Workspace::find($workspaceId)->uuid;
            $route = env('CACHE_CLEAR_URL') . "/{$workspaceUuid}/{$pattern}";
        } else {
            $route = env('CACHE_CLEAR_URL') . "/all";
        }

        try {
            $http = new \GuzzleHttp\Client();
            $http->headers([
                'X-webhook-secret' => env('WEBHOOK_SECRET')
            ]);
            $http->get($route);
        } catch (\Exception $e) {
            Log::critical('Failed to invoke clear cache: '.$e->getMessage());
        }
    }
}
