<?php

declare(strict_types=1);

namespace Yukazakiri\LaravelDatabaseSync\Actions\Postgres;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Yukazakiri\LaravelDatabaseSync\Classes\Config;
use Yukazakiri\LaravelDatabaseSync\Console\DatabaseSyncCommand;

final class DumpSchemaForTablesAction
{
    public static function handle(Collection $tables, Config $config, DatabaseSyncCommand $command, bool $dumpFullSchema = false): void
    {
        if (!$dumpFullSchema && $tables->isEmpty()) {
            return;
        }

        if ($command->isDebug()) {
            $message = $dumpFullSchema
                ? __('Dumping full schema for local bootstrap...')
                : __('Dumping schema for missing tables: :tables', ['tables' => $tables->implode(', ')]);
            $command->line($message);
        }

        $dumpFlags = config('database-sync.postgres.schema_dump_flags');
        $tableFlags = $dumpFullSchema ? '' : $tables->map(fn(string $table) => "--table={$table}")->implode(' ');
        $dumpCommand = mb_trim("{$config->pg_dump_binary} -h localhost -U {$config->remote_database_username} {$dumpFlags} {$tableFlags} {$config->remote_database}");
        $exportCommand = "ssh -o ControlMaster=auto -o ControlPath=/tmp/ssh_mux_%h_%p -o ControlPersist=10m {$config->remote_user_and_host} \"PGPASSWORD='{$config->remote_database_password}' " . $dumpCommand . " >> {$config->remote_temporary_file}\"";

        $process = Process::timeout($config->process_timeout);
        $result = $process->run($exportCommand);

        if ($result->failed()) {
            throw new Exception(__('Failed to export schema for missing tables: :error', [
                'error' => $result->errorOutput(),
            ]));
        }

        if ($command->isDebug() && mb_trim($result->output()) !== '') {
            $command->line($result->output());
        }
    }
}
