<?php

declare(strict_types=1);

namespace Marshmallow\LaravelDatabaseSync\Actions\Postgres;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Marshmallow\LaravelDatabaseSync\Classes\Config;
use Marshmallow\LaravelDatabaseSync\Console\DatabaseSyncCommand;

final class GetMissingLocalTablesAction
{
    public static function handle(Collection $tables, Config $config, DatabaseSyncCommand $command): Collection
    {
        if ($tables->isEmpty()) {
            return collect();
        }

        $tableList = $tables
            ->map(fn (string $table) => "'".str_replace("'", "''", $table)."'")
            ->implode(', ');

        $query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name IN ({$tableList})";
        $psqlCommand = "PGPASSWORD='{$config->local_database_password}' psql -h {$config->local_host} -U {$config->local_database_username} -d {$config->local_database} -t -c \"{$query}\"";

        $process = Process::timeout($config->process_timeout);
        $result = $process->run($psqlCommand);

        if ($result->failed()) {
            throw new Exception(__('Failed to check local tables: :error', ['error' => $result->errorOutput()]));
        }

        $existingTables = collect(explode("\n", mb_trim($result->output())))
            ->map(fn (string $table) => mb_trim($table))
            ->filter();

        $missingTables = $tables->diff($existingTables)->values();

        if ($command->isDebug() && $missingTables->isNotEmpty()) {
            $command->line(__('Missing local tables: :tables', [
                'tables' => $missingTables->implode(', '),
            ]));
        }

        return $missingTables;
    }
}
