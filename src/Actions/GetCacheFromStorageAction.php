<?php

namespace Yukazakiri\LaravelDatabaseSync\Actions;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Yukazakiri\LaravelDatabaseSync\Classes\Config;

class GetCacheFromStorageAction
{
    public static function handle(
        Config $config,
        ?array $default = null
    ): ?array {
        $contents = Storage::disk($config->cache_file_disk)->get($config->cache_file_path);
        if (!$contents) {
            return $default;
        }

        if (!Str::isJson($contents)) {
            return $default;
        }

        return json_decode($contents, true);
    }
}
