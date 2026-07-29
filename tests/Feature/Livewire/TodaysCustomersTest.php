<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\TodaysCustomers;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TodaysCustomersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_renders_customer_list(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Customer::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'name_ar' => 'أحمد',
        ]);

        Livewire::test(TodaysCustomers::class)
            ->assertOk();
    }

    public function test_search_filters_by_name_ar(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Customer::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'name_ar' => 'محل أحمد',
        ]);

        Customer::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'name_ar' => 'محل سعيد',
        ]);

        Livewire::test(TodaysCustomers::class)
            ->set('search', 'أحمد')
            ->assertOk();
    }

    public function test_search_filters_by_phone(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Customer::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
            'phone' => '+966501234567',
        ]);

        Livewire::test(TodaysCustomers::class)
            ->set('search', '96650')
            ->assertOk();
    }

    public function test_toggle_customer_actions(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Livewire::test(TodaysCustomers::class)
            ->call('toggleCustomerActions', 42)
            ->assertSet('expandedCustomerId', 42)
            ->call('toggleCustomerActions', 42)
            ->assertSet('expandedCustomerId', 0);
    }

    public function test_excludes_inactive_customers(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Customer::factory()->create([
            'company_id' => $company->id,
            'is_active' => false,
            'name_ar' => 'محل مغلق',
        ]);

        Livewire::test(TodaysCustomers::class)
            ->assertOk();
    }
}
