<?php

namespace Marshmallow\LaravelDatabaseSync\Drivers;

use Marshmallow\LaravelDatabaseSync\Contracts\DatabaseDriverInterface;
use Marshmallow\LaravelDatabaseSync\Classes\Config;
use Marshmallow\LaravelDatabaseSync\Console\DatabaseSyncCommand;
use Marshmallow\LaravelDatabaseSync\Actions\Mysql\CollectTableAction;
use Marshmallow\LaravelDatabaseSync\Actions\Mysql\CollectStamplessTablesAction;
use Marshmallow\LaravelDatabaseSync\Actions\Mysql\HasDeletedAtColumn;
use Marshmallow\LaravelDatabaseSync\Actions\Mysql\CountRecordsAction;
use Marshmallow\LaravelDatabaseSync\Actions\Mysql\DumpCreatedOrUpdatedDataAction;
use Marshmallow\LaravelDatabaseSync\Actions\Mysql\DumpDeletedDataAction;
use Marshmallow\LaravelDatabaseSync\Actions\Mysql\DumpFullTableDataAction;
use Marshmallow\LaravelDatabaseSync\Actions\Mysql\ImportDataAction;

class MysqlDriver implements DatabaseDriverInterface
{
    public function collectTables(Config $config, DatabaseSyncCommand $command): \Illuminate\Support\Collection
    {
        return CollectTableAction::handle($config, $command);
    }

    public function collectStamplessTables(Config $config, DatabaseSyncCommand $command): \Illuminate\Support\Collection
    {
        return CollectStamplessTablesAction::handle($config, $command);
    }

    public function hasDeletedAtColumn(string $table, Config $config): bool
    {
        return HasDeletedAtColumn::handle($table, $config);
    }

    public function countRecords(string $table, bool $deletedAtAvailable, Config $config, DatabaseSyncCommand $command): int
    {
        return CountRecordsAction::handle($table, $deletedAtAvailable, $config, $command);
    }

    public function dumpCreatedOrUpdatedData(string $table, Config $config, DatabaseSyncCommand $command): void
    {
        DumpCreatedOrUpdatedDataAction::handle($table, $config, $command);
    }

    public function dumpDeletedData(string $table, bool $deletedAtAvailable, Config $config, DatabaseSyncCommand $command): void
    {
        DumpDeletedDataAction::handle($table, $deletedAtAvailable, $config, $command);
    }

    public function dumpFullTableData(string $table, Config $config, DatabaseSyncCommand $command): void
    {
        DumpFullTableDataAction::handle($table, $config, $command);
    }

    public function importData(Config $config, DatabaseSyncCommand $command): void
    {
        ImportDataAction::handle($config, $command);
    }
}