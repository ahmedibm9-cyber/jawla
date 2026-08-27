<?php

namespace Tests\Feature;

use App\Livewire\App\Requests;
use App\Models\Company;
use App\Models\User;
use App\Services\RequestService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RequestTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->company = Company::factory()->create();
        app(ActiveCompanyContext::class)->setCompanyId($this->company->id);

        $this->rep = User::factory()->for($this->company)->create();
        $this->rep->assignRole('sales_rep');

        $this->manager = User::factory()->for($this->company)->create();
        $this->manager->assignRole('sales_manager');
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    public function test_create_request(): void
    {
        $service = app(RequestService::class);

        $request = $service->create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'type' => 'discount',
            'title' => 'Need 10% discount',
            'description' => 'For bulk order',
        ]);

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'type' => 'discount',
            'status' => 'new',
        ]);
    }

    public function test_approve_request(): void
    {
        $service = app(RequestService::class);
        $request = $service->create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'type' => 'leave',
            'title' => 'Day off',
            'description' => 'Personal day',
        ]);

        $approved = $service->approve($request, $this->manager, 'Approved');

        $this->assertEquals('approved', $approved->fresh()->status);
        $this->assertEquals($this->manager->id, $approved->fresh()->reviewed_by);
    }

    public function test_reject_request(): void
    {
        $service = app(RequestService::class);
        $request = $service->create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'type' => 'other',
            'title' => 'Something',
            'description' => 'Test request',
        ]);

        $rejected = $service->reject($request, $this->manager, 'Not needed');

        $this->assertEquals('rejected', $rejected->fresh()->status);
    }

    public function test_requests_page_renders(): void
    {
        $this->actingAs($this->rep);

        Livewire::test(Requests::class)
            ->assertStatus(200);
    }
}
