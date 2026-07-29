<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\PriceQuotation;
use App\Models\PriceQuotationRequest;
use App\Models\Product;
use App\Models\User;
use App\Services\ProformaService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->company = Company::factory()->create(['vat_percent' => 15]);
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
    $this->product = Product::factory()->create(['company_id' => $this->company->id, 'price' => 200, 'vat_applicable' => true]);
});

test('creates proforma from quotation', function () {
    $request = PriceQuotationRequest::create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'product_id' => $this->product->id,
        'user_id' => $this->user->id,
        'quantity_requested' => 5,
        'status' => 'priced',
        'requested_at' => now(),
    ]);
    $quotation = PriceQuotation::create([
        'price_quotation_request_id' => $request->id,
        'base_price' => 100,
        'rep_plus' => 0,
        'rep_minus' => 0,
        'priced_by' => $this->user->id,
        'priced_at' => now(),
    ]);

    $proforma = app(ProformaService::class)->createFromQuotation($quotation, [
        'user_id' => $this->user->id,
    ]);

    $this->assertNotNull($proforma->id);
    $this->assertSame($this->company->id, $proforma->company_id);
    $this->assertSame($this->customer->id, $proforma->customer_id);
    $this->assertSame($quotation->id, $proforma->price_quotation_id);
    $this->assertSame('sent', $proforma->status);
    $this->assertCount(1, $proforma->items);
});

test('proforma has correct calculations', function () {
    $request = PriceQuotationRequest::create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'product_id' => $this->product->id,
        'user_id' => $this->user->id,
        'quantity_requested' => 10,
        'status' => 'priced',
        'requested_at' => now(),
    ]);
    $quotation = PriceQuotation::create([
        'price_quotation_request_id' => $request->id,
        'base_price' => 100,
        'rep_plus' => 0,
        'rep_minus' => 0,
        'priced_by' => $this->user->id,
        'priced_at' => now(),
    ]);

    $proforma = app(ProformaService::class)->createFromQuotation($quotation, [
        'user_id' => $this->user->id,
        'quantity' => 10,
        'unit_price' => 100,
    ]);

    $this->assertSame(1000.0, (float) $proforma->subtotal);
    $vat = round(1000 * 0.15, 2);
    $this->assertSame($vat, (float) $proforma->vat_amount);
    $this->assertSame(round(1000 + $vat, 2), (float) $proforma->total);
    $item = $proforma->items->first();
    $this->assertSame(10.0, (float) $item->quantity);
    $this->assertSame(100.0, (float) $item->unit_price);
    $this->assertSame(1000.0, (float) $item->line_total);
});

test('proforma assigns next number', function () {
    $request = PriceQuotationRequest::create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'product_id' => $this->product->id,
        'user_id' => $this->user->id,
        'quantity_requested' => 1,
        'status' => 'priced',
        'requested_at' => now(),
    ]);
    $quotation = PriceQuotation::create([
        'price_quotation_request_id' => $request->id,
        'base_price' => 50,
        'rep_plus' => 0,
        'rep_minus' => 0,
        'priced_by' => $this->user->id,
        'priced_at' => now(),
    ]);

    $proforma = app(ProformaService::class)->createFromQuotation($quotation, [
        'user_id' => $this->user->id,
    ]);

    $this->assertNotEmpty($proforma->proforma_number);
    $second = app(ProformaService::class)->createFromQuotation($quotation, [
        'user_id' => $this->user->id,
    ]);
    $this->assertNotSame($proforma->proforma_number, $second->proforma_number);
});

test('proforma without bank account', function () {
    $request = PriceQuotationRequest::create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'product_id' => $this->product->id,
        'user_id' => $this->user->id,
        'quantity_requested' => 2,
        'status' => 'priced',
        'requested_at' => now(),
    ]);
    $quotation = PriceQuotation::create([
        'price_quotation_request_id' => $request->id,
        'base_price' => 75,
        'rep_plus' => 0,
        'rep_minus' => 0,
        'priced_by' => $this->user->id,
        'priced_at' => now(),
    ]);

    $proforma = app(ProformaService::class)->createFromQuotation($quotation, [
        'user_id' => $this->user->id,
    ]);

    $this->assertNull($proforma->company_bank_account_id);
});
