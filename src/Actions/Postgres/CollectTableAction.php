<?php

namespace Yukazakiri\LaravelDatabaseSync\Actions\Postgres;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Yukazakiri\LaravelDatabaseSync\Classes\Config;
use Yukazakiri\LaravelDatabaseSync\Console\DatabaseSyncCommand;

class CollectTableAction
{
    public static function handle(Config $config, DatabaseSyncCommand $command): Collection
    {
        $ignore_table_query = collect(config('database-sync.tables.ignore'))
            ->map(fn($table) => "AND table_name != '{$table}'")
            ->implode(' ');

        $query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE' {$ignore_table_query} AND EXISTS (SELECT 1 FROM information_schema.columns WHERE columns.table_name = tables.table_name AND column_name IN ('created_at', 'updated_at') AND table_schema = 'public')";

        $psqlCommand = "ssh -o ControlMaster=auto -o ControlPath=/tmp/ssh_mux_%h_%p -o ControlPersist=10m {$config->remote_user_and_host} \"PGPASSWORD='{$config->remote_database_password}' psql -h 127.0.0.1 -U {$config->remote_database_username} -d {$config->remote_database} -t -c \\\"{$query}\\\"\"";

        $process = Process::timeout($config->process_timeout);
        $result = $process->run($psqlCommand);

        if ($result->failed()) {
            throw new \Exception(__('Failed to collect tables: :error', ['error' => $result->errorOutput()]));
        }

        return collect(explode("\n", trim($result->output())))
            ->map(fn($table) => trim($table))
            ->filter()
            ->values();
    }
}