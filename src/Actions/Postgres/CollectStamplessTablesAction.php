<?php

namespace Yukazakiri\LaravelDatabaseSync\Actions\Postgres;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Yukazakiri\LaravelDatabaseSync\Classes\Config;
use Yukazakiri\LaravelDatabaseSync\Console\DatabaseSyncCommand;

class CollectStamplessTablesAction
{
    public static function handle(Config $config, DatabaseSyncCommand $command): Collection
    {
        if (!config('database-sync.suites')) {
            return collect();
        }

        $suites = collect(config('database-sync.suites'));
        if (!$suites->count()) {
            return collect();
        }

        $tables_to_include = $suites->flatten()->unique()->values()->toArray();
        $tables_to_include_query = collect($tables_to_include)
            ->map(fn($table) => "table_name = '{$table}'")
            ->implode(' OR ');

        if (empty($tables_to_include_query)) {
            return collect();
        }

        $query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE' AND ({$tables_to_include_query}) AND table_name NOT IN (SELECT DISTINCT table_name FROM information_schema.columns WHERE column_name IN ('created_at', 'updated_at') AND table_schema = 'public')";

        $psqlCommand = "ssh -o ControlMaster=auto -o ControlPath=/tmp/ssh_mux_%h_%p -o ControlPersist=10m {$config->remote_user_and_host} \"PGPASSWORD='{$config->remote_database_password}' psql -h 127.0.0.1 -U {$config->remote_database_username} -d {$config->remote_database} -t -c \\\"{$query}\\\"\"";

        $process = Process::timeout($config->process_timeout);
        $result = $process->run($psqlCommand);

        if ($result->failed()) {
            throw new \Exception(__('Failed to collect stampless tables: :error', ['error' => $result->errorOutput()]));
        }

        return collect(explode("\n", trim($result->output())))
            ->map(fn($table) => trim($table))
            ->filter()
            ->values();
    }
}