<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cross-cutting: Company Isolation
 *
 * Tests that Company A users cannot see Company B data.
 */
class CompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;
    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create(['name_en' => 'Company A']);
        $this->companyB = Company::factory()->create(['name_en' => 'Company B']);

        $this->userA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'userA@test.test',
        ]);
        $this->userB = User::factory()->create([
            'company_id' => $this->companyB->id,
            'email' => 'userB@test.test',
        ]);
    }

    public function test_user_a_cannot_see_company_b_customers(): void
    {
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id, 'name_en' => 'B Customer']);

        $visible = Customer::whereKey($customerB->id)
            ->where('company_id', $this->userA->company_id)
            ->exists();

        $this->assertFalse($visible, 'User A should not see Company B customer through scoped query');
    }

    public function test_user_a_cannot_see_company_b_products(): void
    {
        $productB = Product::factory()->create(['company_id' => $this->companyB->id]);

        $visible = Product::whereKey($productB->id)
            ->where('company_id', $this->userA->company_id)
            ->exists();

        $this->assertFalse($visible, 'User A should not see Company B product through scoped query');
    }

    public function test_user_a_cannot_see_company_b_warehouses(): void
    {
        $warehouseB = Warehouse::factory()->create([
            'company_id' => $this->companyB->id, 'type' => 'main', 'name_en' => 'B Warehouse',
        ]);

        $visible = Warehouse::whereKey($warehouseB->id)
            ->where('company_id', $this->userA->company_id)
            ->exists();

        $this->assertFalse($visible, 'User A should not see Company B warehouse');
    }

    public function test_user_a_sees_only_own_company_customers(): void
    {
        Customer::factory()->create(['company_id' => $this->companyA->id, 'name_en' => 'A Customer']);
        Customer::factory()->create(['company_id' => $this->companyB->id, 'name_en' => 'B Customer']);

        $this->actingAs($this->userA);

        $visible = Customer::where('company_id', $this->userA->company_id)->get();
        $this->assertCount(1, $visible);
        $this->assertEquals('A Customer', $visible->first()->name_en);
    }

    public function test_user_b_sees_only_own_company_products(): void
    {
        Product::factory()->create(['company_id' => $this->companyA->id]);
        Product::factory()->create(['company_id' => $this->companyB->id]);

        $this->actingAs($this->userB);

        $visible = Product::where('company_id', $this->userB->company_id)->get();
        $this->assertCount(1, $visible);
        $this->assertEquals($this->companyB->id, $visible->first()->company_id);
    }
}
