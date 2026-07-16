<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\Customer;
use App\Support\ActiveCompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_a_user_cannot_see_company_b_customers(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        Customer::factory()->create(['company_id' => $companyB->id]);

        $context = app(ActiveCompanyContext::class);
        $context->setCompanyId($companyA->id);

        $this->assertSame(0, Customer::count());
    }

    public function test_company_a_user_sees_own_customers(): void
    {
        $companyA = Company::factory()->create();

        Customer::factory()->create(['company_id' => $companyA->id]);

        $context = app(ActiveCompanyContext::class);
        $context->setCompanyId($companyA->id);

        $this->assertSame(1, Customer::count());
    }

    public function test_disabled_context_sees_all_companies(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        Customer::factory()->create(['company_id' => $companyA->id]);
        Customer::factory()->create(['company_id' => $companyB->id]);

        $context = app(ActiveCompanyContext::class);
        $context->disable();

        $this->assertSame(2, Customer::count());
    }
}
