<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\AddCustomer;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AddCustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_submit_validates_required_fields(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Livewire::test(AddCustomer::class)
            ->call('submit')
            ->assertHasErrors(['name_ar', 'name_en', 'phone']);
    }

    public function test_submit_creates_pending_customer(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Livewire::test(AddCustomer::class)
            ->set('name_ar', 'عميل جديد')
            ->set('name_en', 'New Customer')
            ->set('phone', '+966501234567')
            ->call('submit')
            ->assertSet('successMessage', fn ($msg) => $msg !== null && $msg !== '');

        $this->assertDatabaseHas('customers', [
            'company_id' => $company->id,
            'name_ar' => 'عميل جديد',
            'name_en' => 'New Customer',
            'status' => 'pending',
        ]);
    }

    public function test_submit_raises_alarm(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        User::factory()->create(['company_id' => $company->id])->assignRole('sales_manager');
        $this->actingAs($rep);

        Livewire::test(AddCustomer::class)
            ->set('name_ar', 'عميل')
            ->set('name_en', 'Customer')
            ->set('phone', '+966501234567')
            ->call('submit');

        $this->assertDatabaseHas('alarms', [
            'company_id' => $company->id,
            'type' => 'new_customer_pending',
        ]);
    }

    public function test_submit_resets_form(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Livewire::test(AddCustomer::class)
            ->set('name_ar', 'عميل')
            ->set('name_en', 'Customer')
            ->set('phone', '+966501234567')
            ->set('address', '123 Main St')
            ->call('submit')
            ->assertSet('name_ar', '')
            ->assertSet('name_en', '')
            ->assertSet('phone', '')
            ->assertSet('address', '');
    }
}
