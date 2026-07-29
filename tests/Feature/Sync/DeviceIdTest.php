<?php

namespace Tests\Feature\Sync;

use App\Models\Company;
use App\Models\User;
use App\Services\Sync\Contracts\SyncHandler;
use App\Services\Sync\SyncHandlerRegistry;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceIdTest extends TestCase
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

            public function handle(User $rep, array $payload, ?string $idempotencyKey = null): array
            {
                return ['ok' => true];
            }
        });
    }

    public function test_device_id_header_is_stored_in_receipt(): void
    {
        $this->stubHandler();

        $this->actingAs($this->rep)
            ->withHeaders(['X-Device-Id' => 'device-abc-123'])
            ->postJson('/app/sync', [
                'operations' => [['key' => 'd1', 'type' => 'noop', 'payload' => []]],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'applied');

        $this->assertDatabaseHas('sync_receipts', [
            'idempotency_key' => 'd1',
            'device_id' => 'device-abc-123',
        ]);
    }

    public function test_device_id_per_operation_overrides_header(): void
    {
        $this->stubHandler();

        $this->actingAs($this->rep)
            ->withHeaders(['X-Device-Id' => 'header-device'])
            ->postJson('/app/sync', [
                'operations' => [['key' => 'd2', 'type' => 'noop', 'payload' => [], 'deviceId' => 'op-device']],
            ])
            ->assertOk();

        $this->assertDatabaseHas('sync_receipts', [
            'idempotency_key' => 'd2',
            'device_id' => 'op-device',
        ]);
    }

    public function test_missing_device_id_results_in_null(): void
    {
        $this->stubHandler();

        $this->actingAs($this->rep)
            ->postJson('/app/sync', [
                'operations' => [['key' => 'd3', 'type' => 'noop', 'payload' => []]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('sync_receipts', [
            'idempotency_key' => 'd3',
            'device_id' => null,
        ]);
    }
}
