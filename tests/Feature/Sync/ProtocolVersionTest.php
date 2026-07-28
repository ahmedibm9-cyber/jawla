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

class ProtocolVersionTest extends TestCase
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

    public function test_endpoint_accepts_request_without_version_header(): void
    {
        $this->stubHandler();

        $this->actingAs($this->rep)
            ->postJson('/app/sync', [
                'operations' => [['key' => 'v1', 'type' => 'noop', 'payload' => []]],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'applied');

        $this->assertDatabaseHas('sync_receipts', [
            'idempotency_key' => 'v1',
            'protocol_version' => 1,
        ]);
    }

    public function test_endpoint_stores_version_from_header(): void
    {
        $this->stubHandler();

        $this->actingAs($this->rep)
            ->withHeaders(['X-Sync-Protocol-Version' => '2'])
            ->postJson('/app/sync', [
                'operations' => [['key' => 'v2', 'type' => 'noop', 'payload' => []]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('sync_receipts', [
            'idempotency_key' => 'v2',
            'protocol_version' => 2,
        ]);
    }
}
