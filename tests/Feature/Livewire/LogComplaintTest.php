<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\LogComplaint;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LogComplaintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function rep(Company $company): User
    {
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        return $rep;
    }

    public function test_submit_validates_required_fields(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        Livewire::test(LogComplaint::class)
            ->set('complaint_type', '')
            ->call('submit')
            ->assertHasErrors(['customer_id', 'complaint_type', 'description']);
    }

    public function test_submit_creates_complaint(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'status' => 'approved']);
        $this->actingAs($rep);

        Livewire::test(LogComplaint::class)
            ->set('customer_id', $customer->id)
            ->set('complaint_type', 'quality_issue')
            ->set('description', 'Product quality does not match specifications')
            ->call('submit')
            ->assertSet('successMessage', fn ($msg) => $msg !== null)
            ->assertSet('customer_id', null)
            ->assertSet('complaint_type', 'other')
            ->assertSet('description', '');

        $this->assertDatabaseHas('complaints', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $rep->id,
            'complaint_type' => 'quality_issue',
            'status' => 'open',
        ]);
    }

    public function test_queue_offline_sets_success(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        Livewire::test(LogComplaint::class)
            ->set('customer_id', 1)
            ->set('description', 'test')
            ->call('queueOffline')
            ->assertSet('successMessage', fn ($msg) => str_contains((string) $msg, 'offline') || str_contains((string) $msg, 'دون اتصال'))
            ->assertSet('customer_id', null)
            ->assertSet('complaint_type', 'other')
            ->assertSet('description', '');
    }
}
