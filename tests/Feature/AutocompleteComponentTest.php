<?php

namespace Tests\Feature;

use App\Livewire\App\CollectPayment;
use App\Livewire\App\LogComplaint;
use App\Livewire\App\LogReturn;
use App\Livewire\App\SubmitPurchaseOffer;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Server-render smoke tests for the four rep pages using
 * accessible Alpine autocomplete. These catch Blade-compile errors and
 * Livewire model extraction bugs; interactive filtering is covered by the browser suite.
 */
class AutocompleteComponentTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->company = Company::factory()->create();
        $this->rep = User::factory()->create(['company_id' => $this->company->id]);
        $this->rep->assignRole('rep');

        // Van warehouse so stock-backed pages render.
        Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'van',
            'user_id' => $this->rep->id,
        ]);

        Customer::factory()->create(['company_id' => $this->company->id, 'name_ar' => 'عميل تجريبي']);
        Product::factory()->create(['company_id' => $this->company->id, 'name_ar' => 'منتج تجريبي']);
        Supplier::factory()->create(['company_id' => $this->company->id, 'name_ar' => 'مورد تجريبي']);
    }

    public function test_collect_payment_renders_customer_autocomplete(): void
    {
        Livewire::actingAs($this->rep)
            ->test(CollectPayment::class)
            ->assertOk()
            ->assertSeeHtml('role="combobox"')
            ->assertSeeHtml('role="listbox"')
            ->assertSeeHtml('id="customer_id-hidden"');
    }

    public function test_log_complaint_renders_autocomplete_and_keeps_confirmation_modal(): void
    {
        Livewire::actingAs($this->rep)
            ->test(LogComplaint::class)
            ->assertOk()
            ->assertSeeHtml('role="combobox"')
            // Confirmation modal must remain intact after the migration.
            ->assertSee(__('app.confirm_complaint_title'));
    }

    public function test_log_return_renders_customer_and_product_autocompletes(): void
    {
        Livewire::actingAs($this->rep)
            ->test(LogReturn::class)
            ->assertOk()
            ->assertSeeHtml('id="customer_id-hidden"')
            ->assertSee(__('app.search_product'))
            ->assertSee(__('app.confirm_return_title'));
    }

    public function test_submit_purchase_offer_renders_product_and_supplier_autocompletes(): void
    {
        Livewire::actingAs($this->rep)
            ->test(SubmitPurchaseOffer::class)
            ->assertOk()
            ->assertSeeHtml('id="product_id-hidden"')
            ->assertSee(__('app.search_supplier'));
    }
}
