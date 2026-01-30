<?php

declare(strict_types=1);

namespace Yukazakiri\LaravelDatabaseSync\Actions\Postgres;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Yukazakiri\LaravelDatabaseSync\Classes\Config;
use Yukazakiri\LaravelDatabaseSync\Console\DatabaseSyncCommand;

final class GetLocalTablesAction
{
    public static function handle(Config $config, DatabaseSyncCommand $command): Collection
    {
        $query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
        $psqlCommand = "PGPASSWORD='{$config->local_database_password}' psql -h {$config->local_host} -U {$config->local_database_username} -d {$config->local_database} -t -c \"{$query}\"";

        $process = Process::timeout($config->process_timeout);
        $result = $process->run($psqlCommand);

        if ($result->failed()) {
            throw new Exception(__('Failed to check local tables: :error', ['error' => $result->errorOutput()]));
        }

        return collect(explode("\n", mb_trim($result->output())))
            ->map(fn(string $table) => mb_trim($table))
            ->filter();
    }
}
