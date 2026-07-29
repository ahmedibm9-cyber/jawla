<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\QuotationFlow;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PriceQuotation;
use App\Models\PriceQuotationRequest;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuotationFlowTest extends TestCase
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

    private function quotationRequest(Company $company, User $rep, float $basePrice, float $repPlus, float $repMinus): PriceQuotationRequest
    {
        $product = Product::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'status' => 'approved']);

        $request = PriceQuotationRequest::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $rep->id,
            'product_id' => $product->id,
            'quantity_requested' => 10,
            'status' => 'priced',
            'requested_at' => now(),
        ]);

        PriceQuotation::create([
            'price_quotation_request_id' => $request->id,
            'base_price' => $basePrice,
            'rep_plus' => $repPlus,
            'rep_minus' => $repMinus,
            'manager_plus' => 0,
            'manager_minus' => 0,
            'priced_by' => $rep->id,
            'priced_at' => now(),
        ]);

        return $request->load('quotation', 'product', 'customer', 'company');
    }

    public function test_mount_sets_list_step(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        Livewire::test(QuotationFlow::class)
            ->assertSet('step', 'list');
    }

    public function test_select_quotation_sets_detail_step(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $request = $this->quotationRequest($company, $rep, 100.0, 10.0, 10.0);
        $this->actingAs($rep);

        Livewire::test(QuotationFlow::class)
            ->call('selectQuotation', $request->id)
            ->assertSet('step', 'detail')
            ->assertSet('negotiatedPrice', 100.0)
            ->assertSet('floor', 90.0)
            ->assertSet('ceiling', 110.0);
    }

    public function test_confirm_price_validates_range(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $request = $this->quotationRequest($company, $rep, 100.0, 10.0, 10.0);
        $this->actingAs($rep);

        Livewire::test(QuotationFlow::class)
            ->call('selectQuotation', $request->id)
            ->set('negotiatedPrice', 0)
            ->call('confirmPrice')
            ->assertHasErrors(['negotiatedPrice']);
    }

    public function test_confirm_price_within_range_sets_success(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $request = $this->quotationRequest($company, $rep, 100.0, 10.0, 10.0);
        $this->actingAs($rep);

        Livewire::test(QuotationFlow::class)
            ->call('selectQuotation', $request->id)
            ->set('negotiatedPrice', 105.0)
            ->call('confirmPrice')
            ->assertSet('successMessage', fn ($msg) => $msg !== null)
            ->assertSet('step', 'detail');

        $this->assertDatabaseHas('price_quotation_requests', [
            'id' => $request->id,
            'status' => 'confirmed',
            'negotiated_price' => 105.0,
        ]);
    }

    public function test_create_proforma_requires_confirmed_status(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $request = $this->quotationRequest($company, $rep, 100.0, 10.0, 10.0);
        $this->actingAs($rep);

        Livewire::test(QuotationFlow::class)
            ->call('selectQuotation', $request->id)
            ->set('negotiatedPrice', 105.0)
            ->call('createProforma')
            ->assertSet('errorMessage', fn ($msg) => str_contains((string) $msg, 'confirmed') || str_contains((string) $msg, 'تأكيد'));
    }
}
