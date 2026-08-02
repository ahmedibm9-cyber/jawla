<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Models\User;
use App\Services\EgyptQrStrategy;
use App\Services\EtaQrStrategy;
use App\Services\InvoiceQrService;
use App\Services\ZatcaPhase1Strategy;
use App\Services\ZatcaPhase2Strategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceQrServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_saudi_phase_2_strategy_when_csid_present(): void
    {
        $company = Company::factory()->create([
            'country' => 'SA',
            'zatca_enabled' => true,
            'zatca_csid' => 'test-csid-123',
            'zatca_secret' => 'test-secret',
            'zatca_environment' => 'sandbox',
            'name_ar' => 'شركة اختبار',
            'tax_number' => '300000000000003',
        ]);

        $service = app(InvoiceQrService::class);

        $strategy = $service->resolveStrategy($company, 'invoice');

        $this->assertInstanceOf(ZatcaPhase2Strategy::class, $strategy);
    }

    public function test_saudi_phase_1_strategy_when_csid_missing(): void
    {
        $company = Company::factory()->create([
            'country' => 'SA',
            'zatca_enabled' => true,
            'zatca_csid' => null,
            'name_ar' => 'شركة اختبار',
            'tax_number' => '300000000000003',
        ]);

        $service = app(InvoiceQrService::class);

        $strategy = $service->resolveStrategy($company, 'invoice');

        $this->assertInstanceOf(ZatcaPhase1Strategy::class, $strategy);
    }

    public function test_saudi_no_zatca_falls_back_to_egypt(): void
    {
        $company = Company::factory()->create([
            'country' => 'SA',
            'zatca_enabled' => false,
            'zatca_csid' => 'test-csid',
            'name_ar' => 'شركة اختبار',
            'tax_number' => '300000000000003',
        ]);

        $service = app(InvoiceQrService::class);

        $strategy = $service->resolveStrategy($company, 'invoice');

        $this->assertInstanceOf(EgyptQrStrategy::class, $strategy);
    }

    public function test_egypt_company_uses_egypt_strategy(): void
    {
        $company = Company::factory()->create([
            'country' => 'EG',
            'zatca_enabled' => false,
            'name_ar' => 'شركة مصرية',
            'tax_number' => '200000000000002',
        ]);

        $service = app(InvoiceQrService::class);

        $strategy = $service->resolveStrategy($company, 'invoice');

        $this->assertInstanceOf(EgyptQrStrategy::class, $strategy);
    }

    public function test_same_logic_applies_to_proforma(): void
    {
        $company = Company::factory()->create([
            'country' => 'SA',
            'zatca_enabled' => true,
            'zatca_csid' => 'test-csid',
            'name_ar' => 'شركة اختبار',
            'tax_number' => '300000000000003',
        ]);

        $service = app(InvoiceQrService::class);

        $strategy = $service->resolveStrategy($company, 'proforma');

        $this->assertInstanceOf(ZatcaPhase2Strategy::class, $strategy);
    }

    public function test_generate_for_invoice_returns_string(): void
    {
        $company = Company::factory()->create([
            'country' => 'EG',
            'zatca_enabled' => false,
            'name_ar' => 'شركة مصرية',
            'tax_number' => '200000000000002',
        ]);

        $customer = Customer::factory()->create([
            'company_id' => $company->id,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'invoice_number' => 'INV-2026-00001',
            'total' => 1140.00,
            'vat_amount' => 140.00,
            'status' => 'submitted',
        ]);

        $service = app(InvoiceQrService::class);

        $qr = $service->generateForInvoice($invoice);

        $this->assertIsString($qr);
        $this->assertNotEmpty($qr);
    }

    public function test_generate_for_proforma_returns_string(): void
    {
        $company = Company::factory()->create([
            'country' => 'EG',
            'zatca_enabled' => false,
            'name_ar' => 'شركة مصرية',
            'tax_number' => '200000000000002',
        ]);

        $customer = Customer::factory()->create([
            'company_id' => $company->id,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $proforma = ProformaInvoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'proforma_number' => 'PF-2026-00001',
            'total' => 1140.00,
            'vat_amount' => 140.00,
            'status' => 'sent',
        ]);

        $service = app(InvoiceQrService::class);

        $qr = $service->generateForProforma($proforma);

        $this->assertIsString($qr);
        $this->assertNotEmpty($qr);
    }

    public function test_eta_enabled_uses_eta_strategy(): void
    {
        $company = Company::factory()->create([
            'country' => 'EG',
            'eta_enabled' => true,
            'name_ar' => 'شركة مصرية',
            'tax_number' => '200000000000002',
        ]);

        $service = app(InvoiceQrService::class);
        $strategy = $service->resolveStrategy($company, 'invoice');

        $this->assertInstanceOf(EtaQrStrategy::class, $strategy);
    }

    public function test_eta_disabled_falls_back_to_egypt(): void
    {
        $company = Company::factory()->create([
            'country' => 'EG',
            'eta_enabled' => false,
            'name_ar' => 'شركة مصرية',
            'tax_number' => '200000000000002',
        ]);

        $service = app(InvoiceQrService::class);
        $strategy = $service->resolveStrategy($company, 'invoice');

        $this->assertInstanceOf(EgyptQrStrategy::class, $strategy);
    }

    public function test_eta_takes_priority_over_zatca_for_egypt(): void
    {
        $company = Company::factory()->create([
            'country' => 'EG',
            'eta_enabled' => true,
            'zatca_enabled' => true,
            'zatca_csid' => 'test-csid',
            'name_ar' => 'شركة مصرية',
            'tax_number' => '200000000000002',
        ]);

        $service = app(InvoiceQrService::class);
        $strategy = $service->resolveStrategy($company, 'invoice');

        $this->assertInstanceOf(EtaQrStrategy::class, $strategy);
    }
}
