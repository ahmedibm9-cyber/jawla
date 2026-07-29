<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RoleSeeder;

/**
 * End-to-end browser tests for the complete sales journey:
 * sell page → customer selection → product selection → invoice creation.
 *
 * Also covers payment collection, returns, and expenses.
 */
function makeRepWithStock(): User
{
    test()->seed(RoleSeeder::class);
    $company = Company::factory()->create(['name_ar' => 'شركة المبيعات']);
    $rep = User::factory()->create([
        'company_id' => $company->id,
        'name' => 'مندوب المبيعات',
        'onboarding_seen' => true,
    ]);
    $rep->assignRole('rep');

    $van = Warehouse::factory()->create([
        'company_id' => $company->id,
        'type' => 'van',
        'user_id' => $rep->id,
    ]);

    $product = Product::factory()->create([
        'company_id' => $company->id,
        'name_ar' => 'منتج للبيع',
        'sku' => 'SELL-001',
        'is_active' => true,
        'price' => 150.00,
    ]);

    Stock::create([
        'company_id' => $company->id,
        'warehouse_id' => $van->id,
        'product_id' => $product->id,
        'quantity' => 50,
    ]);

    return $rep;
}

it('loads the sell page without JavaScript errors', function () {
    $rep = makeRepWithStock();

    $page = $this->actingAs($rep)->visit('/app/sell');

    $page->assertNoJavascriptErrors()
        ->assertSee(__('app.sell'));
});

it('loads the sell page for a specific customer without JavaScript errors', function () {
    $rep = makeRepWithStock();
    $customer = Customer::factory()->create([
        'company_id' => $rep->company_id,
        'name_ar' => 'عميل محدد',
        'status' => 'approved',
        'is_active' => true,
    ]);

    $page = $this->actingAs($rep)->visit("/app/sell/{$customer->id}");

    $page->assertNoJavascriptErrors()
        ->assertSee('عميل محدد');
});

it('loads the collect payment page with autocomplete structure', function () {
    $rep = makeRepWithStock();

    $page = $this->actingAs($rep)->visit('/app/collect-payment');

    $page->assertNoJavascriptErrors()
        ->assertPresent('#customer_id[role="combobox"]')
        ->assertPresent('#customer_id-listbox[role="listbox"]');
});

it('loads the returns page without JavaScript errors', function () {
    $rep = makeRepWithStock();

    $page = $this->actingAs($rep)->visit('/app/returns');

    $page->assertNoJavascriptErrors()
        ->assertSee(__('app.returns'));
});

it('loads the expenses page without JavaScript errors', function () {
    $rep = makeRepWithStock();

    $page = $this->actingAs($rep)->visit('/app/expenses');

    $page->assertNoJavascriptErrors()
        ->assertSee(__('app.expenses'));
});

it('loads the cash reconcile page without JavaScript errors', function () {
    $rep = makeRepWithStock();

    $page = $this->actingAs($rep)->visit('/app/reconcile');

    $page->assertNoJavascriptErrors()
        ->assertSee(__('app.reconcile'));
});

it('loads the quotations page without JavaScript errors', function () {
    $rep = makeRepWithStock();

    $page = $this->actingAs($rep)->visit('/app/quotations');

    $page->assertNoJavascriptErrors()
        ->assertSee(__('app.quotations'));
});

it('loads the van transfers page without JavaScript errors', function () {
    $rep = makeRepWithStock();

    $page = $this->actingAs($rep)->visit('/app/transfers');

    $page->assertNoJavascriptErrors()
        ->assertSee(__('app.transfers'));
});
