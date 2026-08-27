<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\LogCall;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\User;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LogCallTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    private function rep(Company $company): User
    {
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        return $rep;
    }

    public function test_save_validates_required_fields(): void
    {
        $company = Company::factory()->create();
        app(ActiveCompanyContext::class)->setCompanyId($company->id);
        $rep = $this->rep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'route_id' => null]);
        $this->actingAs($rep);

        Livewire::test(LogCall::class, ['customerId' => $customer->id])
            ->set('durationSeconds', 0)
            ->call('saveCall')
            ->assertHasErrors(['durationSeconds']);
    }

    public function test_save_call(): void
    {
        $company = Company::factory()->create();
        app(ActiveCompanyContext::class)->setCompanyId($company->id);
        $rep = $this->rep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'route_id' => null]);
        $this->actingAs($rep);

        Livewire::test(LogCall::class, ['customerId' => $customer->id])
            ->set('direction', 'outbound')
            ->set('durationSeconds', 120)
            ->set('outcome', 'reached')
            ->set('notes', 'Discussed order status')
            ->call('saveCall')
            ->assertSet('successMessage', fn ($msg) => $msg !== null);

        $this->assertDatabaseHas('calls', [
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'direction' => 'outbound',
            'duration_seconds' => 120,
            'outcome' => 'reached',
        ]);
    }

    public function test_save_call_with_contact(): void
    {
        $company = Company::factory()->create();
        app(ActiveCompanyContext::class)->setCompanyId($company->id);
        $rep = $this->rep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'route_id' => null]);
        $contact = CustomerContact::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'name' => 'Test Contact',
            'phone' => '+1234567890',
            'position' => 'Manager',
        ]);
        $this->actingAs($rep);

        Livewire::test(LogCall::class, ['customerId' => $customer->id])
            ->set('contactId', $contact->id)
            ->set('direction', 'inbound')
            ->set('durationSeconds', 60)
            ->set('outcome', 'reached')
            ->call('saveCall')
            ->assertSet('successMessage', fn ($msg) => $msg !== null);

        $this->assertDatabaseHas('calls', [
            'contact_id' => $contact->id,
            'direction' => 'inbound',
        ]);
    }

    public function test_start_stop_timer(): void
    {
        $company = Company::factory()->create();
        app(ActiveCompanyContext::class)->setCompanyId($company->id);
        $rep = $this->rep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'route_id' => null]);
        $this->actingAs($rep);

        Livewire::test(LogCall::class, ['customerId' => $customer->id])
            ->assertSet('isRunning', false)
            ->assertSet('durationSeconds', 0)
            ->call('startTimer')
            ->assertSet('isRunning', true)
            ->call('stopTimer')
            ->assertSet('isRunning', false);
    }
}
