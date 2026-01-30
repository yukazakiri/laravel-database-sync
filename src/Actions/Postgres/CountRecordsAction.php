<?php

namespace Yukazakiri\LaravelDatabaseSync\Actions\Postgres;

use Illuminate\Support\Facades\Process;
use Yukazakiri\LaravelDatabaseSync\Classes\Config;
use Yukazakiri\LaravelDatabaseSync\Console\DatabaseSyncCommand;
use Yukazakiri\LaravelDatabaseSync\Exceptions\OutputWarningException;

class CountRecordsAction
{
    public static function handle(string $table, bool $deletedAtAvailable, Config $config, DatabaseSyncCommand $command): int
    {
        $deletedWhere = $deletedAtAvailable ? ' AND deleted_at IS NOT NULL' : '';

        $timestamps = GetTableTimestampColumns::handle($table, $config);
        $conditions = [];
        if (in_array('created_at', $timestamps))
            $conditions[] = "created_at >= '{$config->date}'";
        if (in_array('updated_at', $timestamps))
            $conditions[] = "updated_at >= '{$config->date}'";

        if (empty($conditions)) {
            // Should not happen if collectTables works correctly
            return 0;
        }

        $whereClause = implode(' OR ', $conditions);
        $countCreatedOrUpdatedQuery = "SELECT COUNT(*) FROM {$table} WHERE ({$whereClause})";
        $countDeletedQuery = $deletedAtAvailable ? "SELECT COUNT(*) FROM {$table} WHERE deleted_at >= '{$config->date}'" : "SELECT 0";

        $countCreatedOrUpdatedCommand = "ssh -o ControlMaster=auto -o ControlPath=/tmp/ssh_mux_%h_%p -o ControlPersist=10m {$config->remote_user_and_host} \"PGPASSWORD='{$config->remote_database_password}' psql -h 127.0.0.1 -U {$config->remote_database_username} -d {$config->remote_database} -t -c \\\"{$countCreatedOrUpdatedQuery}\\\"\"";
        $countDeletedCommand = "ssh -o ControlMaster=auto -o ControlPath=/tmp/ssh_mux_%h_%p -o ControlPersist=10m {$config->remote_user_and_host} \"PGPASSWORD='{$config->remote_database_password}' psql -h 127.0.0.1 -U {$config->remote_database_username} -d {$config->remote_database} -t -c \\\"{$countDeletedQuery}\\\"\"";

        $process = Process::timeout($config->process_timeout);

        $createdOrUpdatedResult = $process->run($countCreatedOrUpdatedCommand);
        if ($createdOrUpdatedResult->failed()) {
            throw new \Exception(__('Failed to count created/updated records: :error', ['error' => $createdOrUpdatedResult->errorOutput()]));
        }

        $deletedResult = $process->run($countDeletedCommand);
        if ($deletedResult->failed()) {
            throw new \Exception(__('Failed to count deleted records: :error', ['error' => $deletedResult->errorOutput()]));
        }

        $createdOrUpdatedCount = (int) trim($createdOrUpdatedResult->output());
        $deletedCount = (int) trim($deletedResult->output());
        $totalCount = $createdOrUpdatedCount + $deletedCount;

        if ($totalCount === 0) {
            throw new OutputWarningException(__(':table: No new records found', ['table' => $table]));
        }

        $command->info(__(":table: :count records", [
            'table' => $table,
            'count' => $totalCount,
        ]));

        return $totalCount;
    }
}