<?php

namespace Tests\Feature\Sync;

use App\Models\Company;
use App\Models\User;
use App\Services\Sync\Contracts\SyncHandler;
use App\Services\Sync\SyncHandlerRegistry;
use App\Services\Sync\SyncService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayloadHashTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->company = Company::factory()->create();
        $this->rep = User::factory()->create(['company_id' => $this->company->id]);
        $this->rep->assignRole('rep');
        app(ActiveCompanyContext::class)->setFromUser($this->rep);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    private function stubHandler(): void
    {
        app(SyncHandlerRegistry::class)->register('noop', new class implements SyncHandler
        {
            public function type(): string
            {
                return 'noop';
            }

            public function handle(User $rep, array $payload): array
            {
                return ['ok' => true];
            }
        });
    }

    private function hashPayload(string $type, array $payload): string
    {
        return hash('sha256', json_encode(['type' => $type, 'payload' => $payload], JSON_THROW_ON_ERROR));
    }

    public function test_same_key_same_payload_returns_duplicate(): void
    {
        $this->stubHandler();
        $svc = app(SyncService::class);

        $hash = $this->hashPayload('noop', ['foo' => 'bar']);

        $first = $svc->process($this->rep, [
            ['key' => 'dup1', 'type' => 'noop', 'payload' => ['foo' => 'bar'], 'payload_hash' => $hash],
        ]);

        $this->assertSame('applied', $first[0]['status']);

        $second = $svc->process($this->rep, [
            ['key' => 'dup1', 'type' => 'noop', 'payload' => ['foo' => 'bar'], 'payload_hash' => $hash],
        ]);

        $this->assertSame('duplicate', $second[0]['status']);
        $this->assertSame($first[0]['result'], $second[0]['result']);
    }

    public function test_same_key_different_payload_returns_mismatch(): void
    {
        $this->stubHandler();
        $svc = app(SyncService::class);

        $hash1 = $this->hashPayload('noop', ['foo' => 'bar']);
        $hash2 = $this->hashPayload('noop', ['foo' => 'baz']);

        $first = $svc->process($this->rep, [
            ['key' => 'mismatch1', 'type' => 'noop', 'payload' => ['foo' => 'bar'], 'payload_hash' => $hash1],
        ]);

        $this->assertSame('applied', $first[0]['status']);

        $second = $svc->process($this->rep, [
            ['key' => 'mismatch1', 'type' => 'noop', 'payload' => ['foo' => 'baz'], 'payload_hash' => $hash2],
        ]);

        $this->assertSame('mismatch', $second[0]['status']);
        $this->assertSame('Payload mismatch for same idempotency key', $second[0]['error']);
    }

    public function test_same_key_without_hash_falls_back_to_duplicate(): void
    {
        $this->stubHandler();
        $svc = app(SyncService::class);

        $first = $svc->process($this->rep, [
            ['key' => 'nohash1', 'type' => 'noop', 'payload' => ['foo' => 'bar']],
        ]);

        $this->assertSame('applied', $first[0]['status']);

        $second = $svc->process($this->rep, [
            ['key' => 'nohash1', 'type' => 'noop', 'payload' => ['foo' => 'baz']],
        ]);

        $this->assertSame('duplicate', $second[0]['status']);
    }
}
