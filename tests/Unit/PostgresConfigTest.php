<?php

test('it has default mysql driver configuration', function () {
    expect(config('database-sync.database_driver'))->toBe('mysql');
});

test('it has postgres configuration section', function () {
    $postgresConfig = config('database-sync.postgres');
    
    expect($postgresConfig)->toBeArray()
        ->and($postgresConfig)->toHaveKey('dump_action_flags')
        ->and($postgresConfig['dump_action_flags'])->toBe('--no-owner --no-privileges --data-only --column-inserts --on-conflict-do-nothing');
});

test('it can set postgres as database driver', function () {
    config()->set('database-sync.database_driver', 'postgres');
    
    expect(config('database-sync.database_driver'))->toBe('postgres');
});