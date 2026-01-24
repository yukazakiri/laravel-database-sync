<?php

use Marshmallow\LaravelDatabaseSync\Classes\DatabaseDriverManager;
use Marshmallow\LaravelDatabaseSync\Drivers\MysqlDriver;
use Marshmallow\LaravelDatabaseSync\Drivers\PostgresDriver;

test('it returns mysql driver by default', function () {
    config()->set('database-sync.database_driver', 'mysql');
    
    $manager = new DatabaseDriverManager();
    $driver = $manager->driver();
    
    expect($driver)->toBeInstanceOf(MysqlDriver::class);
});

test('it returns postgres driver when configured', function () {
    config()->set('database-sync.database_driver', 'postgres');
    
    $manager = new DatabaseDriverManager();
    $driver = $manager->driver();
    
    expect($driver)->toBeInstanceOf(PostgresDriver::class);
});

test('it throws exception for unsupported driver', function () {
    $manager = new DatabaseDriverManager();
    
    expect(fn() => $manager->driver('unsupported'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported database driver: unsupported');
});

test('it returns supported drivers', function () {
    $manager = new DatabaseDriverManager();
    $supported = $manager->getSupportedDrivers();
    
    expect($supported)->toBe(['mysql', 'postgres']);
});