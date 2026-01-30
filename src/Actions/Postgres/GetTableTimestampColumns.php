<?php

namespace Yukazakiri\LaravelDatabaseSync\Actions\Postgres;

use Illuminate\Support\Facades\Process;
use Yukazakiri\LaravelDatabaseSync\Classes\Config;

class GetTableTimestampColumns
{
    public static function handle(string $table, Config $config): array
    {
        $query = "SELECT column_name FROM information_schema.columns WHERE table_name = '{$table}' AND column_name IN ('created_at', 'updated_at') AND table_schema = 'public'";

        $psqlCommand = "ssh -o ControlMaster=auto -o ControlPath=/tmp/ssh_mux_%h_%p -o ControlPersist=10m {$config->remote_user_and_host} \"PGPASSWORD='{$config->remote_database_password}' psql -h 127.0.0.1 -U {$config->remote_database_username} -d {$config->remote_database} -t -c \\\"{$query}\\\"\"";

        $process = Process::timeout($config->process_timeout);
        $result = $process->run($psqlCommand);

        if ($result->failed()) {
            throw new \Exception(__('Failed to check timestamp columns: :error', ['error' => $result->errorOutput()]));
        }

        return array_values(array_filter(array_map('trim', explode("\n", trim($result->output())))));
    }
}
