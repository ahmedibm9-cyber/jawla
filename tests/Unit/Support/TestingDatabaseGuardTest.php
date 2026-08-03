<?php

use Tests\Support\TestingDatabaseGuard;

it('accepts isolated PostgreSQL test database names', function (string $database): void {
    TestingDatabaseGuard::assertSafe('testing', 'pgsql', $database);

    expect(true)->toBeTrue();
})->with([
    'sequential base' => 'jawla_test',
    'process worker' => 'jawla_test_process_1234',
    'named remediation run' => 'jawla_test_remediation_p0',
    'parallel worker' => 'jawla_test_remediation_p0_test_1',
]);

it('rejects a non-testing application environment', function (): void {
    TestingDatabaseGuard::assertSafe('production', 'pgsql', 'jawla_test');
})->throws(LogicException::class, 'APP_ENV must be testing');

it('rejects a non-PostgreSQL connection', function (): void {
    TestingDatabaseGuard::assertSafe('testing', 'sqlite', 'jawla_test');
})->throws(LogicException::class, 'PostgreSQL');

it('rejects a database outside the dedicated test namespace', function (string $database): void {
    TestingDatabaseGuard::assertSafe('testing', 'pgsql', $database);
})->with([
    'development database' => 'jawla',
    'production-like database' => 'jawla_production',
    'empty database' => '',
])->throws(LogicException::class, 'jawla_test');
