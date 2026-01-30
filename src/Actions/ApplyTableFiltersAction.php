<?php

namespace Yukazakiri\LaravelDatabaseSync\Actions;

use Illuminate\Support\Collection;
use Yukazakiri\LaravelDatabaseSync\Classes\Config;
use Yukazakiri\LaravelDatabaseSync\Filters\RejectTables;
use Yukazakiri\LaravelDatabaseSync\Filters\RejectTenantTables;
use Yukazakiri\LaravelDatabaseSync\Console\DatabaseSyncCommand;
use Yukazakiri\LaravelDatabaseSync\Filters\RejectLandlordTables;
use Yukazakiri\LaravelDatabaseSync\Filters\FilterSuiteTableOption;
use Yukazakiri\LaravelDatabaseSync\Filters\FilterExclusiveTableOption;

class ApplyTableFiltersAction
{
    public static function handle(
        array $tables,
        Config $config,
        DatabaseSyncCommand $command,
    ): Collection {
        return collect($tables)
            ->reject(fn($table) => RejectTables::apply($table))
            ->filter(fn($table) => FilterExclusiveTableOption::apply($table, $command->option('table')))
            ->filter(fn($table) => FilterSuiteTableOption::apply($table, $command->option('suite')))
            ->reject(fn($table) => RejectLandlordTables::apply($table, $config->multi_tenant_database_type))
            ->reject(fn($table) => RejectTenantTables::apply($table, $config->remote_database, $config->multi_tenant_database_type));
    }
}
