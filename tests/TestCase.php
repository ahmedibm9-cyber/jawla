<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\TestingDatabaseGuard;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        TestingDatabaseGuard::assertSafe(
            (string) getenv('APP_ENV'),
            (string) getenv('DB_CONNECTION'),
            (string) getenv('DB_DATABASE'),
        );

        parent::setUp();

        // The base TestCase re-reads .env inside createApplication(). Re-assert
        // the testing environment on the freshly booted app so
        // app()->environment(), app()->runningUnitTests(), and the DB
        // connection all report the truth. Without this, service-level unit
        // tests trip the ActiveCompanyContext tenancy guard, every config that
        // branches on environment misbehaves, and the suite accidentally runs
        // against the development database.
        //
        // The DB connection is NOT purged here. DatabaseTransactions starts
        // its transaction on the connection inside parent::setUp(); purging
        // that connection disposes the transaction context, so the rollback at
        // teardown has nothing to roll back, and every test's writes get
        // committed — which is why tests that allocate INV-...-00001 collide
        // on the unique constraint. _env.php pins the env BEFORE Laravel
        // boots, so the config is already correct on first connection use.
        $this->app->detectEnvironment(fn (): string => 'testing');
    }
}
