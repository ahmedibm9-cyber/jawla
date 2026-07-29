<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\SubmitPurchaseOffer;
use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubmitPurchaseOfferTest extends TestCase
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

        Livewire::test(SubmitPurchaseOffer::class)
            ->call('submit')
            ->assertHasErrors(['product_id', 'quantity', 'offered_price']);
    }

    public function test_submit_creates_purchase_request(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        $product = Product::factory()->create(['company_id' => $company->id]);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);

        Livewire::test(SubmitPurchaseOffer::class)
            ->set('product_id', $product->id)
            ->set('supplier_id', $supplier->id)
            ->set('quantity', 100)
            ->set('offered_price', 50.00)
            ->set('currency', 'EGP')
            ->call('submit')
            ->assertSet('successMessage', fn ($msg) => $msg !== null && $msg !== '');

        $this->assertDatabaseHas('purchase_requests', [
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'offered_price' => 50.00,
            'currency' => 'EGP',
            'status' => 'pending',
        ]);
    }

    public function test_submit_resets_form(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        $product = Product::factory()->create(['company_id' => $company->id]);

        Livewire::test(SubmitPurchaseOffer::class)
            ->set('product_id', $product->id)
            ->set('quantity', 100)
            ->set('offered_price', 50.00)
            ->call('submit')
            ->assertSet('product_id', null)
            ->assertSet('quantity', null)
            ->assertSet('offered_price', null);
    }

    public function test_submit_validates_currency(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        $product = Product::factory()->create(['company_id' => $company->id]);

        Livewire::test(SubmitPurchaseOffer::class)
            ->set('product_id', $product->id)
            ->set('quantity', 100)
            ->set('offered_price', 50.00)
            ->set('currency', 'GBP')
            ->call('submit')
            ->assertHasErrors(['currency']);
    }

    public function test_resubmit_loads_existing_offer(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        $product = Product::factory()->create(['company_id' => $company->id]);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);

        $offer = PurchaseRequest::create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'quantity' => 50,
            'offered_price' => 30.00,
            'currency' => 'EGP',
            'status' => 'rejected_by_sales',
            'sales_review_notes' => 'Price too low',
        ]);

        Livewire::test(SubmitPurchaseOffer::class, ['resubmit' => $offer->id])
            ->assertSet('product_id', $product->id)
            ->assertSet('supplier_id', $supplier->id)
            ->assertSet('quantity', 50.0)
            ->assertSet('offered_price', 30.0)
            ->assertSet('rejectionNotes', 'Price too low');
    }
}
