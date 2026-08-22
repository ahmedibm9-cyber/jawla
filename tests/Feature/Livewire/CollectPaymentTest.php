<?php

namespace Tests\Feature\Livewire;

use App\Enums\InvoiceStatus;
use App\Livewire\App\CollectPayment;
use App\Models\CollectionSubmission;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Photo;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CollectPaymentTest extends TestCase
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

    public function test_renders_with_customers(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        Livewire::test(CollectPayment::class)
            ->assertSuccessful();
    }

    public function test_submit_creates_pending_collection_with_evidence(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        User::factory()->create(['company_id' => $company->id])->assignRole('sales_manager');
        User::factory()->create(['company_id' => $company->id])->assignRole('accounts');
        $customer = Customer::factory()->create(['company_id' => $company->id, 'status' => 'approved']);
        $photo = Photo::factory()->create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'photable_type' => null,
            'photable_id' => null,
        ]);
        $this->actingAs($rep);

        Livewire::test(CollectPayment::class)
            ->set('customer_id', $customer->id)
            ->set('amount', 250.0)
            ->set('method', 'cash')
            ->set('photoIds', [$photo->id])
            ->call('submit')
            ->assertSet('success', true)
            ->assertSet('lastPaymentId', null);

        $submission = CollectionSubmission::query()->firstOrFail();
        $this->assertSame('pending_review', $submission->status);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame($submission->getMorphClass(), $photo->fresh()->photable_type);
        $this->assertSame($submission->id, $photo->fresh()->photable_id);
    }

    public function test_submit_validates_required_fields(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        Livewire::test(CollectPayment::class)
            ->call('submit')
            ->assertHasErrors(['customer_id', 'amount']);
    }

    public function test_submit_validates_method_in_list(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $this->actingAs($rep);

        Livewire::test(CollectPayment::class)
            ->set('customer_id', $customer->id)
            ->set('amount', 100)
            ->set('method', 'bitcoin')
            ->call('submit')
            ->assertHasErrors(['method']);
    }

    public function test_updated_invoice_id_sets_amount_to_remaining(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $rep->id,
            'status' => InvoiceStatus::Submitted,
            'remaining_amount' => 350,
        ]);
        $this->actingAs($rep);

        Livewire::test(CollectPayment::class)
            ->set('customer_id', $customer->id)
            ->set('invoice_id', $invoice->id)
            ->assertSet('amount', 350.0);
    }

    public function test_queue_offline_shows_queued_message(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        Livewire::test(CollectPayment::class)
            ->call('queueOffline')
            ->assertSet('success', true)
            ->assertSet('successMessage', fn ($msg) => str_contains($msg, 'offline') || str_contains($msg, 'دون اتصال'));
    }
}
