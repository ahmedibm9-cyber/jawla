<?php

namespace Tests\Unit\Services;

use App\Models\CashBox;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\RepProvisioningService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepProvisioningServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_provision_creates_van_warehouse_and_cashbox(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        app(RepProvisioningService::class)->provision($rep);

        $this->assertDatabaseHas('warehouses', [
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'type' => 'van',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('cash_boxes', [
            'user_id' => $rep->id,
            'company_id' => $company->id,
            'balance' => 0,
        ]);
    }

    public function test_provision_is_idempotent(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        $service = app(RepProvisioningService::class);
        $service->provision($rep);
        $service->provision($rep);

        $this->assertEquals(1, Warehouse::where('user_id', $rep->id)->where('type', 'van')->count());
        $this->assertEquals(1, CashBox::where('user_id', $rep->id)->count());
    }

    public function test_provision_creates_warehouse_with_bilingual_names(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id, 'name' => 'Ahmed']);
        $rep->assignRole('sales_rep');

        app(RepProvisioningService::class)->provision($rep);

        $warehouse = Warehouse::where('user_id', $rep->id)->where('type', 'van')->first();
        $this->assertStringContainsString('Ahmed', $warehouse->name_ar);
        $this->assertStringContainsString('Ahmed', $warehouse->name_en);
    }
}
