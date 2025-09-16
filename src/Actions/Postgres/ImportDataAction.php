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

        $importCommand = "PGPASSWORD='{$config->local_database_password}' psql -h {$config->local_host} -U {$config->local_database_username} -d {$config->local_database} -f {$config->local_temporary_file}";

        $process = Process::timeout($config->process_timeout);
        $result = $process->run($importCommand);

        if ($result->failed()) {
            throw new \Exception(__('Failed to import data to local database: :error', ['error' => $result->errorOutput()]));
        }
    }
}