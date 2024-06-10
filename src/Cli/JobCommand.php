<?php
namespace Budgetcontrol\jobs\Cli;

use Carbon\Carbon;
use Symfony\Component\Console\Command\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Http;
use Ramsey\Uuid\Uuid;

abstract class JobCommand extends Command
{
    const JOBS_TABLE = 'failed_jobs';
    protected string $command;

    protected function fail(string $exception): void
    {
        Log::error('Job failed '.$exception);
        $query = "INSERT INTO ".self::JOBS_TABLE." (uuid, command, exception, failed_at) VALUES 
        ('". Uuid::uuid4() ."', '".$this->command."', '".$exception."', '".Carbon::now()->toAtomString()."')";
        Db::insert($query);
    }

    protected function heartbeats(string $key): void
    {
        $http = new \GuzzleHttp\Client();
        $http->head('https://uptime.betterstack.com/api/v1/heartbeat/'.$key);
    }
}