<?php

namespace Tests\Unit\Services;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SalesTarget;
use App\Models\User;
use App\Services\AttainmentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttainmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_actual_for_rep_sums_invoices_in_period(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        Invoice::factory()->create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'total' => '500.00',
            'posting_date' => now()->subDays(5),
            'status' => InvoiceStatus::Submitted,
        ]);

        Invoice::factory()->create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'total' => '300.00',
            'posting_date' => now()->subDays(3),
            'status' => InvoiceStatus::Submitted,
        ]);

        $actual = app(AttainmentService::class)->actualForRep($rep->id, now()->subWeek(), now());

        $this->assertEqualsWithDelta(800.0, $actual, 0.01);
    }

    public function test_actual_for_rep_excludes_cancelled(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        Invoice::factory()->create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'total' => '500.00',
            'posting_date' => now()->subDays(2),
            'status' => InvoiceStatus::Submitted,
        ]);

        Invoice::factory()->create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'total' => '200.00',
            'posting_date' => now()->subDays(1),
            'status' => InvoiceStatus::Cancelled,
        ]);

        $actual = app(AttainmentService::class)->actualForRep($rep->id, now()->subWeek(), now());

        $this->assertEqualsWithDelta(500.0, $actual, 0.01);
    }

    public function test_attainment_calculates_percent(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $target = SalesTarget::factory()->create([
            'user_id' => $rep->id,
            'company_id' => $company->id,
            'target_amount' => '1000.00',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
        ]);

        Invoice::factory()->create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'total' => '400.00',
            'posting_date' => now(),
            'status' => InvoiceStatus::Submitted,
        ]);

        $result = app(AttainmentService::class)->attainment($target);

        $this->assertEqualsWithDelta(40.0, $result['percent'], 0.1);
        $this->assertEqualsWithDelta(400.0, $result['actual'], 0.01);
        $this->assertEqualsWithDelta(600.0, $result['remaining'], 0.01);
    }

    public function test_current_target_returns_matching_target(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        $target = SalesTarget::factory()->create([
            'user_id' => $rep->id,
            'company_id' => $company->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
        ]);

        $result = app(AttainmentService::class)->currentTargetForRep($rep->id);

        $this->assertNotNull($result);
        $this->assertSame($target->id, $result->id);
    }

    public function test_current_target_returns_null_when_none(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);

        $result = app(AttainmentService::class)->currentTargetForRep($rep->id);

        $this->assertNull($result);
    }
}
