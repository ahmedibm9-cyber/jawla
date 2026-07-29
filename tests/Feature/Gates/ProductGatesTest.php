<?php

namespace Tests\Feature\Gates;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ProductGatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create([
            'company_id' => Company::factory()->create()->id,
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_manage_prices(): void
    {
        $user = $this->makeUser('admin');

        $this->assertTrue(Gate::forUser($user)->allows('products.manage_prices'));
    }

    public function test_accounts_can_manage_prices(): void
    {
        $user = $this->makeUser('accounts');

        $this->assertTrue(Gate::forUser($user)->allows('products.manage_prices'));
    }

    public function test_sales_manager_cannot_manage_prices(): void
    {
        $user = $this->makeUser('sales_manager');

        $this->assertFalse(Gate::forUser($user)->allows('products.manage_prices'));
    }

    public function test_rep_cannot_manage_prices(): void
    {
        $user = $this->makeUser('rep');

        $this->assertFalse(Gate::forUser($user)->allows('products.manage_prices'));
    }

    public function test_admin_can_view_cost(): void
    {
        $user = $this->makeUser('admin');

        $this->assertTrue(Gate::forUser($user)->allows('products.view_cost'));
    }

    public function test_accounts_can_view_cost(): void
    {
        $user = $this->makeUser('accounts');

        $this->assertTrue(Gate::forUser($user)->allows('products.view_cost'));
    }

    public function test_legacy_executive_cannot_view_cost(): void
    {
        $user = $this->makeUser('executive');

        $this->assertFalse(Gate::forUser($user)->allows('products.view_cost'));
    }

    public function test_system_viewer_can_view_cost_read_only(): void
    {
        $user = $this->makeUser('system_viewer');

        $this->assertTrue(Gate::forUser($user)->allows('products.view_cost'));
    }

    public function test_rep_cannot_view_cost(): void
    {
        $user = $this->makeUser('rep');

        $this->assertFalse(Gate::forUser($user)->allows('products.view_cost'));
    }
}
