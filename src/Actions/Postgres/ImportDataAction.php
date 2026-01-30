<?php

declare(strict_types=1);

namespace Yukazakiri\LaravelDatabaseSync\Actions\Postgres;

use Exception;
use Illuminate\Support\Facades\Process;
use Yukazakiri\LaravelDatabaseSync\Classes\Config;
use Yukazakiri\LaravelDatabaseSync\Console\DatabaseSyncCommand;

final class ImportDataAction
{
    public static function handle(
        Config $config,
        DatabaseSyncCommand $command,
    ): void {
        // Import into local database
        if ($command->isDebug()) {
            $command->info(__('Importing new data into local database...'));
        }

        if (!file_exists($config->local_temporary_file)) {
            throw new Exception(__('Local dump file not found before import: :path', ['path' => $config->local_temporary_file]));
        }

        if (filesize($config->local_temporary_file) === 0) {
            throw new Exception(__('Local dump file is empty before import: :path', ['path' => $config->local_temporary_file]));
        }

        if ($command->isDebug()) {
            $command->line(__('Importing into local database: :database on :host as :user', [
                'database' => $config->local_database,
                'host' => $config->local_host,
                'user' => $config->local_database_username,
            ]));
            $command->line(__('Dump file size: :size bytes', [
                'size' => filesize($config->local_temporary_file),
            ]));
        }

        // Disable foreign key checks during import by setting session_replication_role to replica
        // This allows inserting data in any order regardless of foreign key dependencies
        $importCommand = "PGPASSWORD='{$config->local_database_password}' psql -h {$config->local_host} -U {$config->local_database_username} -d {$config->local_database} -v ON_ERROR_STOP=1 -c 'SET session_replication_role = replica;' -f {$config->local_temporary_file} -c 'SET session_replication_role = DEFAULT;'";

        $process = Process::timeout($config->process_timeout);
        $result = $process->run($importCommand);

        if ($result->failed()) {
            throw new Exception(__('Failed to import data to local database: :error', ['error' => $result->errorOutput()]));
        }

        if ($command->isDebug()) {
            if (mb_trim($result->output()) !== '') {
                $command->line($result->output());
            }

            if (mb_trim($result->errorOutput()) !== '') {
                $command->line($result->errorOutput());
            }
        }
    }
}
