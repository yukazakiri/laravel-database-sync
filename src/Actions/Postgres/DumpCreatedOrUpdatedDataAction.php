<?php

namespace Yukazakiri\LaravelDatabaseSync\Actions\Postgres;

use Illuminate\Support\Facades\Process;
use Yukazakiri\LaravelDatabaseSync\Classes\Config;
use Yukazakiri\LaravelDatabaseSync\Console\DatabaseSyncCommand;

class DumpCreatedOrUpdatedDataAction
{
    public static function handle(
        string $table,
        Config $config,
        DatabaseSyncCommand $command,
    ): void {
        $dump_flags = config('database-sync.postgres.dump_action_flags');

        $timestamps = GetTableTimestampColumns::handle($table, $config);
        $conditions = [];
        if (in_array('created_at', $timestamps))
            $conditions[] = "created_at >= '{$config->date}'";
        if (in_array('updated_at', $timestamps))
            $conditions[] = "updated_at >= '{$config->date}'";

        if (empty($conditions)) {
            return;
        }

        $whereClause = implode(' OR ', $conditions);

        // Use pg_dump with a custom query approach
        $dump_flags = config('database-sync.postgres.dump_action_flags');

        // Create a temporary query to get only the needed records
        $queryCommand = "SELECT * FROM {$table} WHERE {$whereClause}";
        $dumpCommand = "{$config->pg_dump_binary} -h localhost -U {$config->remote_database_username} {$dump_flags} --table={$table} {$config->remote_database}";

        $exportCommand = "ssh -o ControlMaster=auto -o ControlPath=/tmp/ssh_mux_%h_%p -o ControlPersist=10m {$config->remote_user_and_host} \"PGPASSWORD='{$config->remote_database_password}' " . $dumpCommand . " >> {$config->remote_temporary_file}\"";

        if ($command->isDebug()) {
            $command->info(__("Exporting new or updated records for :table...", [
                'table' => $table,
            ]));
        }

        $process = Process::timeout($config->process_timeout);
        $result = $process->run($exportCommand);

        if ($result->failed()) {
            throw new \Exception(__('Failed to export created/updated records for :table: :error', [
                'table' => $table,
                'error' => $result->errorOutput(),
            ]));
        }

        if ($command->isDebug() && trim($result->output()) !== '') {
            $command->line($result->output());
        }
    }
}