<?php
namespace Budgetcontrol\jobs\Cli;

use Carbon\Carbon;
use Symfony\Component\Console\Command\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Capsule\Manager as DB;

abstract class JobCommand extends Command
{
    const JOBS_TABLE = 'jobs';
    protected string $command;

    protected function fail(string $exception): void
    {
        Log::error('Job failed '.$exception);
        $query = "INSERT INTO ".self::JOBS_TABLE." (command, exception, failed_at) VALUES ('".$this->command."', '".$exception."', '".Carbon::now()->toAtomString()."')";
        Db::insert($query);
    }
}