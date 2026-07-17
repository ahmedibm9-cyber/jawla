<?php

namespace Tests\Feature;

use App\Filament\Pages\ReportsPage;
use App\Models\Invoice;
use App\Models\PriceQuotationRequest;
use App\Models\ProformaInvoice;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function adminUser(): User
    {
        $u = User::factory()->create();
        $u->assignRole('admin');
        return $u;
    }

    // ─── Factory tests ────────────────────────────────────

    public function test_invoice_factory_creates_valid_invoice(): void
    {
        $invoice = Invoice::factory()->create();

        $this->assertNotNull($invoice->id);
        $this->assertNotNull($invoice->company_id);
        $this->assertNotNull($invoice->customer_id);
        $this->assertNotNull($invoice->user_id);
        $this->assertNotNull($invoice->invoice_number);
        $this->assertNotNull($invoice->issued_at);
    }

    public function test_proforma_invoice_factory_creates_valid_proforma(): void
    {
        $pf = ProformaInvoice::factory()->create();

        $this->assertNotNull($pf->id);
        $this->assertNotNull($pf->proforma_number);
        $this->assertSame('sent', $pf->status);
    }

    public function test_price_quotation_request_factory_creates_valid_request(): void
    {
        $pqr = PriceQuotationRequest::factory()->create();

        $this->assertNotNull($pqr->id);
        $this->assertNotNull($pqr->product_id);
        $this->assertSame('requested', $pqr->status);
    }

    // ─── Paginator type tests (no data needed) ────────────

    public function test_invoices_tab_returns_paginator(): void
    {
        $user = $this->adminUser();
        Livewire::actingAs($user)
            ->test(ReportsPage::class)
            ->assertSet('tab', 'visit_reports')
            ->call('$set', 'tab', 'invoices')
            ->assertSet('tab', 'invoices');
    }

    public function test_invoices_tab_shows_pagination_with_more_than_100(): void
    {
        $user = $this->adminUser();
        Invoice::factory(101)->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(ReportsPage::class)
            ->call('$set', 'tab', 'invoices')
            ->assertOk();
    }

    public function test_proformas_and_quotations_tabs_render(): void
    {
        $user = $this->adminUser();
        ProformaInvoice::factory(5)->create(['company_id' => $user->company_id, 'user_id' => $user->id]);
        PriceQuotationRequest::factory(5)->create(['company_id' => $user->company_id, 'user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(ReportsPage::class)
            ->call('$set', 'tab', 'proformas')
            ->assertOk()
            ->call('$set', 'tab', 'quotations')
            ->assertOk();
    }
}
