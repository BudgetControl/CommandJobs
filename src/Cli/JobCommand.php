<?php
namespace Budgetcontrol\jobs\Cli;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
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
}
