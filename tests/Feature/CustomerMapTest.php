<?php

namespace Tests\Feature;

use App\Filament\Pages\CustomerMap;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerMapTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->company = Company::factory()->create();
    }

    private function user(string $role): User
    {
        $u = User::factory()->create(['company_id' => $this->company->id]);
        $u->assignRole($role);

        return $u;
    }

    public function test_access_gated_to_management_roles(): void
    {
        $this->actingAs($this->user('sales_manager'));
        $this->assertTrue(CustomerMap::canAccess());

        $this->actingAs($this->user('rep'));
        $this->assertFalse(CustomerMap::canAccess());
    }

    public function test_only_geolocated_customers_of_the_company_are_plotted(): void
    {
        Customer::factory()->create(['company_id' => $this->company->id, 'name_ar' => 'مع موقع', 'latitude' => 30.1, 'longitude' => 31.2]);
        Customer::factory()->create(['company_id' => $this->company->id, 'name_ar' => 'بدون موقع', 'latitude' => null, 'longitude' => null]);
        $other = Company::factory()->create();
        Customer::factory()->create(['company_id' => $other->id, 'latitude' => 10, 'longitude' => 10]);

        $this->actingAs($this->user('sales_manager'));
        $points = Livewire::test(CustomerMap::class)->assertOk()->instance()->points;

        $this->assertCount(1, $points);
        $this->assertSame('مع موقع', $points[0]['name']);
        $this->assertSame(30.1, $points[0]['lat']);
    }

    public function test_renders_empty_state_when_no_geolocated_customers(): void
    {
        $this->actingAs($this->user('sales_manager'));
        $test = Livewire::test(CustomerMap::class)->assertOk();
        $this->assertCount(0, $test->instance()->points);
    }
}
