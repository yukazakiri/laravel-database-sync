<?php

declare(strict_types=1);

test('it has default mysql driver configuration', function () {
    expect(config('database-sync.database_driver'))->toBe('mysql');
});

test('it has postgres configuration section', function () {
    $postgresConfig = config('database-sync.postgres');

    expect($postgresConfig)->toBeArray()
        ->and($postgresConfig)->toHaveKey('dump_action_flags')
        ->and($postgresConfig['dump_action_flags'])->toBe('--no-owner --no-privileges --data-only --column-inserts --on-conflict-do-nothing')
        ->and($postgresConfig)->toHaveKey('pg_dump_binary')
        ->and($postgresConfig['pg_dump_binary'])->toBe('pg_dump')
        ->and($postgresConfig)->toHaveKey('schema_dump_flags')
        ->and($postgresConfig['schema_dump_flags'])->toBe('--schema-only --no-owner --no-privileges')
        ->and($postgresConfig)->toHaveKey('create_missing_tables')
        ->and($postgresConfig['create_missing_tables'])->toBeTrue();
});

test('it can set postgres as database driver', function () {
    config()->set('database-sync.database_driver', 'postgres');

    expect(config('database-sync.database_driver'))->toBe('postgres');
});
