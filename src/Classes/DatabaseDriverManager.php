<?php

namespace Yukazakiri\LaravelDatabaseSync\Classes;

use Yukazakiri\LaravelDatabaseSync\Contracts\DatabaseDriverInterface;
use Yukazakiri\LaravelDatabaseSync\Drivers\MysqlDriver;
use Yukazakiri\LaravelDatabaseSync\Drivers\PostgresDriver;

class DatabaseDriverManager
{
    protected array $drivers = [
        'mysql' => MysqlDriver::class,
        'postgres' => PostgresDriver::class,
    ];

    public function driver(?string $driver = null): DatabaseDriverInterface
    {
        $driver = $driver ?: config('database-sync.database_driver', 'mysql');

        if (!isset($this->drivers[$driver])) {
            throw new \InvalidArgumentException("Unsupported database driver: {$driver}");
        }

        return new $this->drivers[$driver];
    }

    public function getSupportedDrivers(): array
    {
        return array_keys($this->drivers);
    }
}