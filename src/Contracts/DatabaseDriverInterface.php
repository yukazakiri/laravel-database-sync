<?php

namespace Marshmallow\LaravelDatabaseSync\Contracts;

use Marshmallow\LaravelDatabaseSync\Classes\Config;
use Marshmallow\LaravelDatabaseSync\Console\DatabaseSyncCommand;

interface DatabaseDriverInterface
{
    public function collectTables(Config $config, DatabaseSyncCommand $command): \Illuminate\Support\Collection;
    
    public function collectStamplessTables(Config $config, DatabaseSyncCommand $command): \Illuminate\Support\Collection;
    
    public function hasDeletedAtColumn(string $table, Config $config): bool;
    
    public function countRecords(string $table, bool $deletedAtAvailable, Config $config, DatabaseSyncCommand $command): int;
    
    public function dumpCreatedOrUpdatedData(string $table, Config $config, DatabaseSyncCommand $command): void;
    
    public function dumpDeletedData(string $table, bool $deletedAtAvailable, Config $config, DatabaseSyncCommand $command): void;
    
    public function dumpFullTableData(string $table, Config $config, DatabaseSyncCommand $command): void;
    
    public function importData(Config $config, DatabaseSyncCommand $command): void;
}