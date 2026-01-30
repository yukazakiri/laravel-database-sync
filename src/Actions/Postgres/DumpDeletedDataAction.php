<?php

namespace Yukazakiri\LaravelDatabaseSync\Actions\Postgres;

use Illuminate\Support\Facades\Process;
use Yukazakiri\LaravelDatabaseSync\Classes\Config;
use Yukazakiri\LaravelDatabaseSync\Console\DatabaseSyncCommand;

class DumpDeletedDataAction
{
    public static function handle(
        string $table,
        bool $deletedAtAvailable,
        Config $config,
        DatabaseSyncCommand $command,
    ): void {
        if (!$deletedAtAvailable) {
            return;
        }

        $dump_flags = config('database-sync.postgres.dump_action_flags');
        $dumpCommand = "{$config->pg_dump_binary} -h localhost -U {$config->remote_database_username} {$dump_flags} --table={$table} {$config->remote_database}";

        $exportCommand = "ssh -o ControlMaster=auto -o ControlPath=/tmp/ssh_mux_%h_%p -o ControlPersist=10m {$config->remote_user_and_host} \"PGPASSWORD='{$config->remote_database_password}' " . $dumpCommand . " >> {$config->remote_temporary_file}\"";

        if ($command->isDebug()) {
            $command->info(__("Exporting deleted records for :table...", [
                'table' => $table,
            ]));
        }

        $process = Process::timeout($config->process_timeout);
        $result = $process->run($exportCommand);

        if ($result->failed()) {
            throw new \Exception(__('Failed to export deleted records for :table: :error', [
                'table' => $table,
                'error' => $result->errorOutput(),
            ]));
        }

        if ($command->isDebug() && trim($result->output()) !== '') {
            $command->line($result->output());
        }
    }
}