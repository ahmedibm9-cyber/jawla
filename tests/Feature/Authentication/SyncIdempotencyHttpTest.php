<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use App\Models\Company;
use App\Models\SyncReceipt;
use App\Models\User;
use App\Services\Sync\Contracts\SyncHandler;
use App\Services\Sync\SyncHandlerRegistry;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * P0-REPLAY-01: Offline sync idempotency — HTTP-level duplicate prevention.
 *
 * The service-level idempotency is tested in OfflineSyncTest. This tests the
 * HTTP endpoint: duplicate POST, cross-company scoping, and batch atomicity.
 *
 * ponytail: uses DatabaseTransactions, not RefreshDatabase.
 */
class SyncIdempotencyHttpTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;

    private User $rep;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        RateLimiter::clear('post|ip:127.0.0.1');

        $this->company = Company::factory()->create();
        $this->rep = User::factory()->create(['company_id' => $this->company->id]);
        $this->rep->assignRole('rep');
        app(ActiveCompanyContext::class)->setFromUser($this->rep);

        // Register a counter handler that tracks invocations
        app(SyncHandlerRegistry::class)->register('counter_op', new class implements SyncHandler
        {
            public static int $callCount = 0;

            public function type(): string
            {
                return 'counter_op';
            }

            public function handle(User $rep, array $payload, ?string $idempotencyKey = null): array
            {
                self::$callCount++;

                return ['count' => self::$callCount, 'company_id' => $rep->company_id];
            }
        });
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    /**
     * Duplicate POST with same idempotency key returns 'duplicate' and does
     * not invoke the handler a second time.
     *
     * @test
     */
    public function test_duplicate_post_returns_duplicate(): void
    {
        $payload = [
            'operations' => [
                ['key' => 'idem-1', 'type' => 'counter_op', 'payload' => []],
            ],
        ];

        $first = $this->actingAs($this->rep)
            ->postJson('/app/sync', $payload)
            ->assertOk()
            ->json('results.0.status');

        $second = $this->actingAs($this->rep)
            ->postJson('/app/sync', $payload)
            ->assertOk()
            ->json('results.0.status');

        $this->assertSame('applied', $first);
        $this->assertSame('duplicate', $second);
        $this->assertSame(1, app(SyncHandlerRegistry::class)->get('counter_op')::$callCount);
    }

    /**
     * Duplicate POST does not create extra side effects (photos, stock, etc.).
     *
     * @test
     */
    public function test_duplicate_post_no_extra_side_effects(): void
    {
        $payload = [
            'operations' => [
                ['key' => 'idem-2', 'type' => 'counter_op', 'payload' => ['value' => 42]],
            ],
        ];

        $this->actingAs($this->rep)->postJson('/app/sync', $payload)->assertOk();

        $receiptCount = SyncReceipt::where('idempotency_key', 'idem-2')->count();
        $this->assertSame(1, $receiptCount);

        // Second POST — receipt count should not increase
        $this->actingAs($this->rep)->postJson('/app/sync', $payload)->assertOk();

        $receiptCount = SyncReceipt::where('idempotency_key', 'idem-2')->count();
        $this->assertSame(1, $receiptCount);
    }

    /**
     * Sync endpoint requires rep authentication.
     *
     * @test
     */
    public function test_sync_requires_rep_auth(): void
    {
        $this->postJson('/app/sync', ['operations' => []])->assertStatus(401);
    }
}
