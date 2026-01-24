<?php

namespace Marshmallow\LaravelDatabaseSync\Actions\Postgres;

use Illuminate\Support\Facades\Process;
use Marshmallow\LaravelDatabaseSync\Classes\Config;

class HasDeletedAtColumn
{
    public static function handle(string $table, Config $config): bool
    {
        $query = "SELECT COUNT(*) FROM information_schema.columns WHERE table_name = '{$table}' AND column_name = 'deleted_at' AND table_schema = 'public'";

        $psqlCommand = "ssh -o ControlMaster=auto -o ControlPath=/tmp/ssh_mux_%h_%p -o ControlPersist=10m {$config->remote_user_and_host} \"PGPASSWORD='{$config->remote_database_password}' psql -h 127.0.0.1 -U {$config->remote_database_username} -d {$config->remote_database} -t -c \\\"{$query}\\\"\"";

        $process = Process::timeout($config->process_timeout);
        $result = $process->run($psqlCommand);

        if ($result->failed()) {
            throw new \Exception(__('Failed to check deleted_at column: :error', ['error' => $result->errorOutput()]));
        }

        return (int) trim($result->output()) > 0;
    }
}