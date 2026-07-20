<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ComplaintService;
use Database\Seeders\RoleSeeder;

/**
 * End-to-end browser coverage (real Chromium via pest-plugin-browser) for the
 * rep PWA surface, exercised through the in-process HTTP server. Auth is set
 * with the shared-container actingAs; data via factories under RefreshDatabase.
 */
function makeRep(): User
{
    test()->seed(RoleSeeder::class);
    $company = Company::factory()->create(['name_ar' => 'شركة تجريبية']);
    $rep = User::factory()->create(['company_id' => $company->id, 'name' => 'مندوب تجريبي']);
    $rep->assignRole('rep');

    return $rep;
}

it('loads the rep home page without JavaScript errors', function () {
    $rep = makeRep();

    $page = $this->actingAs($rep)->visit('/app');

    $page->assertNoJavascriptErrors()
        ->assertSee($rep->name);
});

it('renders the customer autocomplete on collect payment and filters as you type', function () {
    $rep = makeRep();
    $customer = Customer::factory()->create(['company_id' => $rep->company_id, 'name_ar' => 'عميل الاختبار', 'is_active' => true]);
    Customer::factory()->create(['company_id' => $rep->company_id, 'name_ar' => 'زبون آخر', 'is_active' => true]);

    $page = $this->actingAs($rep)->visit('/app/collect-payment');

    $page->assertNoJavascriptErrors()
        ->assertPresent('#customer_id[role="combobox"]')
        ->type('#customer_id', 'عميل الاختبار')
        ->assertValue('#customer_id', 'عميل الاختبار')
        ->assertValue('#customer_id-hidden', (string) $customer->id);
});

it('renders product and supplier autocompletes on the purchase offer page', function () {
    $rep = makeRep();
    $product = Product::factory()->create(['company_id' => $rep->company_id, 'name_ar' => 'منتج للعرض', 'is_active' => true]);
    $supplier = Supplier::factory()->create(['company_id' => $rep->company_id, 'name_ar' => 'مورد للعرض']);

    $page = $this->actingAs($rep)->visit('/app/purchase-offer');

    $page->assertNoJavascriptErrors()
        ->assertPresent('#product_id[role="combobox"]')
        ->assertPresent('#supplier_id[role="combobox"]')
        ->type('#product_id', 'منتج للعرض')
        ->assertValue('#product_id-hidden', (string) $product->id)
        ->type('#supplier_id', 'مورد للعرض')
        ->assertValue('#supplier_id-hidden', (string) $supplier->id);
});

it('shows the rep purchase offers tab with a submitted offer and its status', function () {
    $rep = makeRep();
    $product = Product::factory()->create(['company_id' => $rep->company_id, 'name_ar' => 'منتج معلق']);
    PurchaseRequest::create([
        'company_id' => $rep->company_id,
        'user_id' => $rep->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'offered_price' => 100,
        'currency' => 'EGP',
        'status' => 'pending',
    ]);

    $page = $this->actingAs($rep)->visit('/app/orders?type=offers');

    $page->assertNoJavascriptErrors()
        ->assertSee('منتج معلق')
        ->assertSee(__('app.status_pending'));
});

it('shows a delivered notification on the rep notifications page', function () {
    $rep = makeRep();
    $customer = Customer::factory()->create(['company_id' => $rep->company_id]);
    $manager = User::factory()->create(['company_id' => $rep->company_id]);
    $manager->assignRole('sales_manager');

    $complaint = app(ComplaintService::class)->log(
        $rep->company_id, $rep->id, $customer->id, 'quality_issue', 'تالف'
    );
    app(ComplaintService::class)->resolve($complaint, $manager->id, 'تم الاستبدال');

    $page = $this->actingAs($rep)->visit('/app/notifications');

    $page->assertNoJavascriptErrors()
        ->assertSee(__('app.notifications'));
});

it('loads the sales flow page without JavaScript errors', function () {
    $rep = makeRep();

    $page = $this->actingAs($rep)->visit('/app/sell');

    $page->assertNoJavascriptErrors();
});

it('renders the rep app in Arabic RTL', function () {
    $rep = makeRep();

    $page = $this->withSession(['locale' => 'ar'])->actingAs($rep)->visit('/app');

    $page->assertNoJavascriptErrors()
        ->assertSourceHas('dir="rtl"');
});

it('renders the rep app in English LTR', function () {
    $rep = makeRep();

    $page = $this->withSession(['locale' => 'en'])->actingAs($rep)->visit('/app');

    $page->assertNoJavascriptErrors()
        ->assertSourceHas('dir="ltr"');
});
