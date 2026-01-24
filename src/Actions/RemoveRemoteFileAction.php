<?php

namespace Marshmallow\LaravelDatabaseSync\Actions;

use Illuminate\Support\Facades\Process;
use Marshmallow\LaravelDatabaseSync\Classes\Config;

class RemoveRemoteFileAction
{
    public static function handle(
        Config $config,
    ): void {
        /**
         * Delete the remote SQL dump file
         */
        $process = Process::timeout($config->process_timeout);
        $process->run("ssh -o ControlMaster=auto -o ControlPath=/tmp/ssh_mux_%h_%p -o ControlPersist=10m {$config->remote_user_and_host} 'rm -f {$config->remote_temporary_file}'");
    }
}
