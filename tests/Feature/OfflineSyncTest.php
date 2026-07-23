<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Photo;
use App\Models\SyncReceipt;
use App\Models\User;
use App\Services\Sync\Contracts\SyncHandler;
use App\Services\Sync\SyncHandlerRegistry;
use App\Services\Sync\SyncService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CG2 offline-sync server foundation: exactly-once application, replay, failure
 * retryability, per-company key scoping, and the rep endpoint envelope. Uses
 * stub handlers registered in the singleton registry — the money-path handlers
 * (sale/payment) are a deliberate follow-up.
 */
class OfflineSyncTest extends TestCase
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

    /** A handler whose side effect (a Photo row) proves how many times it ran. */
    private function photoHandler(): SyncHandler
    {
        return new class implements SyncHandler
        {
            public int $calls = 0;

            public function handle(User $rep, array $payload): array
            {
                $this->calls++;
                $photo = Photo::factory()->create(['company_id' => $rep->company_id, 'user_id' => $rep->id]);

                return ['photo_id' => $photo->id, 'calls' => $this->calls];
            }
        };
    }

    public function test_applies_operation_and_records_receipt(): void
    {
        app(SyncHandlerRegistry::class)->register('make_photo', $this->photoHandler());

        $results = app(SyncService::class)->process($this->rep, [
            ['key' => 'op-1', 'type' => 'make_photo', 'payload' => []],
        ]);

        $this->assertSame('applied', $results[0]['status']);
        $this->assertArrayHasKey('photo_id', $results[0]['result']);
        $this->assertDatabaseHas('sync_receipts', [
            'company_id' => $this->company->id, 'idempotency_key' => 'op-1',
        ]);
        $this->assertSame(1, Photo::count());
    }

    public function test_replayed_key_is_not_reapplied(): void
    {
        $handler = $this->photoHandler();
        app(SyncHandlerRegistry::class)->register('make_photo', $handler);
        $svc = app(SyncService::class);

        $first = $svc->process($this->rep, [['key' => 'dup', 'type' => 'make_photo', 'payload' => []]]);
        $second = $svc->process($this->rep, [['key' => 'dup', 'type' => 'make_photo', 'payload' => []]]);

        $this->assertSame('applied', $first[0]['status']);
        $this->assertSame('duplicate', $second[0]['status']);
        $this->assertSame(1, $handler->calls);          // handler ran exactly once
        $this->assertSame(1, Photo::count());
        $this->assertSame($first[0]['result']['photo_id'], $second[0]['result']['photo_id']);
    }

    public function test_legacy_ambiguous_receipt_is_quarantined_instead_of_replayed(): void
    {
        $handler = $this->photoHandler();
        app(SyncHandlerRegistry::class)->register('make_photo', $handler);
        SyncReceipt::create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'idempotency_key' => 'legacy-unknown',
            'operation_type' => 'make_photo',
            'response' => null,
        ]);

        $result = app(SyncService::class)->process($this->rep, [
            ['key' => 'legacy-unknown', 'type' => 'make_photo', 'payload' => []],
        ]);

        $this->assertSame('conflict', $result[0]['status']);
        $this->assertSame(0, $handler->calls);
        $this->assertDatabaseCount('photos', 0);
    }

    public function test_unsupported_type_does_not_fail_the_batch(): void
    {
        $results = app(SyncService::class)->process($this->rep, [
            ['key' => 'x', 'type' => 'not_registered', 'payload' => []],
        ]);

        $this->assertSame('unsupported', $results[0]['status']);
        $this->assertDatabaseCount('sync_receipts', 0);
    }

    public function test_missing_key_or_type_is_invalid(): void
    {
        $results = app(SyncService::class)->process($this->rep, [
            ['type' => 'make_photo', 'payload' => []],
        ]);

        $this->assertSame('invalid', $results[0]['status']);
    }

    public function test_failed_handler_is_retryable_and_leaves_no_receipt(): void
    {
        app(SyncHandlerRegistry::class)->register('boom', new class implements SyncHandler
        {
            public function handle(User $rep, array $payload): array
            {
                throw new \RuntimeException('handler exploded');
            }
        });

        $results = app(SyncService::class)->process($this->rep, [
            ['key' => 'f1', 'type' => 'boom', 'payload' => []],
        ]);

        $this->assertSame('failed', $results[0]['status']);
        $this->assertStringContainsString('exploded', $results[0]['error']);
        $this->assertDatabaseMissing('sync_receipts', ['idempotency_key' => 'f1']);
    }

    public function test_business_effect_rolls_back_when_the_receipt_cannot_be_finalized(): void
    {
        app(SyncHandlerRegistry::class)->register('broken_receipt', new class implements SyncHandler
        {
            public function handle(User $rep, array $payload): array
            {
                Photo::factory()->create(['company_id' => $rep->company_id, 'user_id' => $rep->id]);

                // Invalid UTF-8 makes Laravel's JSON cast reject the receipt
                // response after the domain handler has performed its write.
                return ['invalid' => "\xB1\x31"];
            }
        });

        $result = app(SyncService::class)->process($this->rep, [
            ['key' => 'atomic-receipt', 'type' => 'broken_receipt', 'payload' => []],
        ]);

        $this->assertSame('failed', $result[0]['status']);
        $this->assertDatabaseCount('photos', 0);
        $this->assertDatabaseMissing('sync_receipts', ['idempotency_key' => 'atomic-receipt']);
    }

    public function test_keys_are_scoped_per_company(): void
    {
        app(SyncHandlerRegistry::class)->register('make_photo', $this->photoHandler());

        $otherCompany = Company::factory()->create();
        $otherRep = User::factory()->create(['company_id' => $otherCompany->id]);

        $svc = app(SyncService::class);
        $a = $svc->process($this->rep, [['key' => 'shared', 'type' => 'make_photo', 'payload' => []]]);
        $b = $svc->process($otherRep, [['key' => 'shared', 'type' => 'make_photo', 'payload' => []]]);

        $this->assertSame('applied', $a[0]['status']);
        $this->assertSame('applied', $b[0]['status']); // same key, different company → not a duplicate
        $this->assertDatabaseCount('sync_receipts', 2);
    }

    public function test_endpoint_requires_rep_auth(): void
    {
        $this->postJson('/app/sync', ['operations' => []])->assertStatus(401);
    }

    public function test_endpoint_applies_a_batch(): void
    {
        app(SyncHandlerRegistry::class)->register('make_photo', $this->photoHandler());

        $this->actingAs($this->rep)
            ->postJson('/app/sync', [
                'operations' => [
                    ['key' => 'e1', 'type' => 'make_photo', 'payload' => []],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'applied');
    }

    public function test_endpoint_validates_the_envelope(): void
    {
        $this->actingAs($this->rep)
            ->postJson('/app/sync', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['operations']);
    }
}
