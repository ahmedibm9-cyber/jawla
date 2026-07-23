<?php

namespace Tests\Feature\Tenancy;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Customer;
use App\Support\ActiveCompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    // RefreshDatabase wraps each test in a transaction and rolls it back, so
    // no truncate() is needed. Assertions below are scoped to the rows each
    // test creates (by company_id / id membership) rather than global counts,
    // so seeded or leaked rows can never make them flake.

    public function test_company_a_user_cannot_see_company_b_customers(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $custB = Customer::factory()->create(['company_id' => $companyB->id]);

        $context = app(ActiveCompanyContext::class);
        $context->setCompanyId($companyA->id);

        $this->assertFalse(Customer::whereKey($custB->id)->exists());
        $this->assertSame(0, Customer::where('company_id', $companyB->id)->count());
    }

    public function test_company_a_user_sees_own_customers(): void
    {
        $companyA = Company::factory()->create();

        $custA = Customer::factory()->create(['company_id' => $companyA->id]);

        $context = app(ActiveCompanyContext::class);
        $context->setCompanyId($companyA->id);

        $this->assertTrue(Customer::whereKey($custA->id)->exists());
        $this->assertSame(1, Customer::where('company_id', $companyA->id)->count());
    }

    public function test_disabled_context_sees_all_companies(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $custA = Customer::factory()->create(['company_id' => $companyA->id]);
        $custB = Customer::factory()->create(['company_id' => $companyB->id]);

        $context = app(ActiveCompanyContext::class);
        $context->disable();

        $visibleIds = Customer::pluck('id');
        $this->assertTrue($visibleIds->contains($custA->id));
        $this->assertTrue($visibleIds->contains($custB->id));
    }

    public function test_company_a_cannot_read_company_b_activity_records(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $activityB = Activity::create([
            'company_id' => $companyB->id,
            'type' => 'invoice_created',
            'description' => 'Company B only',
        ]);

        app(ActiveCompanyContext::class)->setCompanyId($companyA->id);

        $this->assertFalse(Activity::whereKey($activityB->id)->exists());
    }
}
