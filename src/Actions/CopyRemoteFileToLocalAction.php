<?php

namespace Yukazakiri\LaravelDatabaseSync\Actions;

use Illuminate\Support\Facades\Process;
use Yukazakiri\LaravelDatabaseSync\Classes\Config;
use Yukazakiri\LaravelDatabaseSync\Console\DatabaseSyncCommand;

class CopyRemoteFileToLocalAction
{
    public static function handle(
        Config $config,
        DatabaseSyncCommand $command,
    ): void {
        /**
         * Copy the dump file to local
         */
        if ($command->isDebug()) {
            $command->info(__('Copying file to local machine...'));
        }

        $host = $config->remote_user_and_host;
        $portArg = '';

        // Extract port if present in host string (e.g. "user@host -p 2222")
        if (preg_match('/^(.*?)\s+-p\s+(\d+)(.*)$/i', $host, $matches)) {
            $host = trim($matches[1] . $matches[3]);
            $portArg = "-P {$matches[2]}";
        }

        $copyCommand = "scp {$portArg} -o ControlMaster=auto -o ControlPath=/tmp/ssh_mux_%h_%p -o ControlPersist=10m {$host}:{$config->remote_temporary_file} {$config->local_temporary_file}";

        $process = Process::timeout($config->process_timeout);
        $result = $process->run($copyCommand);

        if ($result->failed()) {
            throw new \Exception(__('Failed to copy remote file to local: :error', ['error' => $result->errorOutput()]));
        }

        if (!file_exists($config->local_temporary_file)) {
            throw new \Exception(__('Local dump file not found after copy: :path', ['path' => $config->local_temporary_file]));
        }

        if (filesize($config->local_temporary_file) === 0) {
            throw new \Exception(__('Local dump file is empty after copy: :path', ['path' => $config->local_temporary_file]));
        }
    }
}
