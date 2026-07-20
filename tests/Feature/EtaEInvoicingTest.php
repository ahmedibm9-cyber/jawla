<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Services\Eta\Contracts\EtaClient;
use App\Services\Eta\EtaDocumentBuilder;
use App\Services\Eta\EtaResult;
use App\Services\Eta\EtaService;
use App\Services\Eta\Exceptions\EtaNotConfiguredException;
use App\Services\Eta\NullEtaClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtaEInvoicingTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoice(): Invoice
    {
        $company = Company::factory()->create([
            'tax_number' => '100-200-300',
            'name_en' => 'Jawla Test Co',
            'country' => 'EG',
            'currency' => 'EGP',
            'vat_percent' => 14,
        ]);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'code' => 'C-001', 'name_en' => 'Acme']);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-EG-0001',
            'subtotal' => 1000,
            'vat_amount' => 140,
            'total' => 1140,
        ]);
        $product = Product::factory()->create(['company_id' => $company->id, 'name_ar' => 'صنف', 'sku' => 'SKU9']);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 500,
            'line_total' => 1000,
        ]);

        return $invoice->fresh();
    }

    public function test_document_builder_maps_invoice_to_eta_shape(): void
    {
        $invoice = $this->makeInvoice();

        $doc = app(EtaDocumentBuilder::class)->build($invoice);

        $this->assertSame('100-200-300', $doc['issuer']['id']);
        $this->assertSame('EG', $doc['issuer']['address']['country']);
        $this->assertSame('C-001', $doc['receiver']['id']);
        $this->assertSame('I', $doc['documentType']);
        $this->assertSame('INV-EG-0001', $doc['internalID']);
        $this->assertCount(1, $doc['invoiceLines']);
        $this->assertSame(1000.0, $doc['totalSalesAmount']);
        $this->assertSame(1140.0, $doc['totalAmount']);
        $this->assertSame(140.0, $doc['taxTotals'][0]['amount']);

        $line = $doc['invoiceLines'][0];
        $this->assertSame('SKU9', $line['itemCode']);
        $this->assertSame(2.0, $line['quantity']);
        $this->assertSame(140.0, $line['taxableItems'][0]['amount']); // 1000 * 14%
    }

    public function test_service_is_disabled_by_default_and_refuses_to_submit(): void
    {
        config(['eta.enabled' => false]);
        $invoice = $this->makeInvoice();

        $this->assertFalse(app(EtaService::class)->isEnabled());
        $this->expectException(EtaNotConfiguredException::class);
        app(EtaService::class)->submit($invoice);
    }

    public function test_service_stays_disabled_when_enabled_but_no_taxpayer_rin(): void
    {
        config(['eta.enabled' => true, 'eta.taxpayer_rin' => '']);
        $this->assertFalse(app(EtaService::class)->isEnabled());
    }

    public function test_null_client_is_the_default_binding(): void
    {
        $this->assertInstanceOf(NullEtaClient::class, app(EtaClient::class));
    }

    public function test_submit_records_acceptance_when_configured_with_a_working_client(): void
    {
        config(['eta.enabled' => true, 'eta.taxpayer_rin' => '999888777']);
        $this->app->bind(EtaClient::class, fn () => new class implements EtaClient
        {
            public function submit(array $document): EtaResult
            {
                return EtaResult::accepted('uuid-abc', 'long-123', ['ok' => true]);
            }
        });

        $invoice = $this->makeInvoice();
        $result = app(EtaService::class)->submit($invoice);

        $this->assertSame('submitted', $result->eta_status);
        $this->assertSame('uuid-abc', $result->eta_submission_uuid);
        $this->assertSame('long-123', $result->eta_long_id);
        $this->assertNotNull($result->eta_submitted_at);
    }

    public function test_submit_records_rejection_with_errors(): void
    {
        config(['eta.enabled' => true, 'eta.taxpayer_rin' => '999888777']);
        $this->app->bind(EtaClient::class, fn () => new class implements EtaClient
        {
            public function submit(array $document): EtaResult
            {
                return EtaResult::rejected(['bad tax id']);
            }
        });

        $invoice = $this->makeInvoice();
        $result = app(EtaService::class)->submit($invoice);

        $this->assertSame('rejected', $result->eta_status);
        $this->assertNull($result->eta_submission_uuid);
        $this->assertSame(['errors' => ['bad tax id']], $result->eta_response);
    }
}
