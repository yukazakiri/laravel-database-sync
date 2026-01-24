<?php

namespace Marshmallow\LaravelDatabaseSync\Actions\Postgres;

use Illuminate\Support\Facades\Process;
use Marshmallow\LaravelDatabaseSync\Classes\Config;
use Marshmallow\LaravelDatabaseSync\Console\DatabaseSyncCommand;

class ImportDataAction
{
    public static function handle(
        Config $config,
        DatabaseSyncCommand $command,
    ): void {
        // Import into local database
        if ($command->isDebug()) {
            $command->info(__('Importing new data into local database...'));
        }

        if (! file_exists($config->local_temporary_file)) {
            throw new \Exception(__('Local dump file not found before import: :path', ['path' => $config->local_temporary_file]));
        }

        if (filesize($config->local_temporary_file) === 0) {
            throw new \Exception(__('Local dump file is empty before import: :path', ['path' => $config->local_temporary_file]));
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

        $importCommand = "PGPASSWORD='{$config->local_database_password}' psql -h {$config->local_host} -U {$config->local_database_username} -d {$config->local_database} -v ON_ERROR_STOP=1 -f {$config->local_temporary_file}";

        $process = Process::timeout($config->process_timeout);
        $result = $process->run($importCommand);

        if ($result->failed()) {
            throw new \Exception(__('Failed to import data to local database: :error', ['error' => $result->errorOutput()]));
        }

        if ($command->isDebug()) {
            if (trim($result->output()) !== '') {
                $command->line($result->output());
            }

            if (trim($result->errorOutput()) !== '') {
                $command->line($result->errorOutput());
            }
        }
    }
}