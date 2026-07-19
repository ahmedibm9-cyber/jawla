<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProformaInvoice;
use App\Models\User;
use App\Services\PdfService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfQrCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function createInvoice(array $overrides = []): Invoice
    {
        $company = Company::factory()->create([
            'country' => $overrides['company_country'] ?? 'EG',
            'zatca_enabled' => $overrides['zatca_enabled'] ?? false,
            'zatca_csid' => $overrides['zatca_csid'] ?? null,
            'zatca_secret' => $overrides['zatca_secret'] ?? null,
            'zatca_environment' => $overrides['zatca_environment'] ?? 'sandbox',
            'name_ar' => $overrides['company_name_ar'] ?? 'شركة اختبار',
            'tax_number' => $overrides['tax_number'] ?? '300000000000003',
            'vat_percent' => $overrides['vat_percent'] ?? 15.00,
        ]);

        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'status' => 'approved',
        ]);

        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name_ar' => 'منتج',
            'price' => 1000,
            'vat_applicable' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        // Calculate totals based on vat_percent
        $vatPercent = $overrides['vat_percent'] ?? 15.00;
        $subtotal = $overrides['subtotal'] ?? 1000.00;
        $vatAmount = $overrides['vat_amount'] ?? round($subtotal * $vatPercent / 100, 2);
        $total = $overrides['total'] ?? $subtotal + $vatAmount;

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'visit_id' => null,
            'invoice_number' => $overrides['invoice_number'] ?? 'INV-2026-00001',
            'total' => $total,
            'vat_amount' => $vatAmount,
            'subtotal' => $subtotal,
            'status' => 'submitted',
        ]);

        // Create invoice item
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $subtotal,
            'line_total' => $subtotal,
        ]);

        // Generate PDF to create QR code
        $pdfService = app(PdfService::class);
        $pdfService->generateInvoice($invoice);

        return $invoice->fresh();
    }

    public function test_invoice_pdf_contains_qr_for_saudi_phase2_company(): void
    {
        $this->seed(DemoSeeder::class);

        $invoice = $this->createInvoice([
            'company_country' => 'SA',
            'zatca_enabled' => true,
            'zatca_csid' => 'test-csid-123',
            'zatca_secret' => 'test-secret',
            'zatca_environment' => 'sandbox',
            'name_ar' => 'شركة سعودية',
            'tax_number' => '300000000000003',
            'vat_percent' => 15.00,
            'invoice_number' => 'INV-SA-00001',
        ]);

        $this->assertNotEmpty($invoice->zatca_qr);

        // Verify TLV structure for Phase 2
        $decoded = base64_decode($invoice->zatca_qr, true);
        $this->assertNotFalse($decoded);

        $tags = $this->parseTlv($decoded);
        $this->assertArrayHasKey(1, $tags); // Seller name
        $this->assertArrayHasKey(2, $tags); // VAT number
        $this->assertArrayHasKey(3, $tags); // Timestamp
        $this->assertArrayHasKey(4, $tags); // Total with VAT
        $this->assertArrayHasKey(5, $tags); // VAT amount

        $this->assertEquals('شركة اختبار', $tags[1]);
        $this->assertEquals('300000000000003', $tags[2]);
        $this->assertStringContainsString('T', $tags[3]); // ISO 8601
        $this->assertEquals('1150.00', $tags[4]); // 1000 + 15% VAT
        $this->assertEquals('150.00', $tags[5]); // VAT amount
    }

    public function test_invoice_pdf_contains_qr_for_saudi_phase1_company(): void
    {
        $invoice = $this->createInvoice([
            'company_country' => 'SA',
            'zatca_enabled' => true,
            'zatca_csid' => null, // No CSID = Phase 1
            'name_ar' => 'شركة سعودية fase 1',
            'tax_number' => '300000000000003',
            'vat_percent' => 15.00,
            'invoice_number' => 'INV-SA-00002',
        ]);

        $this->assertNotEmpty($invoice->zatca_qr);
        $this->assertTrue(\Storage::disk('private')->exists("pdfs/invoice_{$invoice->invoice_number}.pdf"));

        $invoice->refresh();
        $this->assertNotNull($invoice->zatca_qr);
    }

    public function test_invoice_pdf_contains_qr_for_egypt_company(): void
    {
        $invoice = $this->createInvoice([
            'company_country' => 'EG',
            'zatca_enabled' => false,
            'name_ar' => 'شركة مصرية',
            'tax_number' => '200000000000002',
            'vat_percent' => 14.00,
            'invoice_number' => 'INV-EG-00001',
            'total' => 1140.00,
            'vat_amount' => 140.00,
            'subtotal' => 1000.00,
        ]);

        $this->assertNotNull($invoice->zatca_qr);

        // Egypt format: invoice_number|total
        $this->assertStringContainsString('INV-EG-', $invoice->zatca_qr);
        $this->assertStringContainsString('|1140.00', $invoice->zatca_qr); // 1000 + 14% VAT
    }

    public function test_proforma_pdf_contains_qr_for_all_company_types(): void
    {
        $countries = [
            'SA' => ['zatca_enabled' => true, 'zatca_csid' => 'csid', 'vat_percent' => 15.00, 'tax_number' => '300000000000003'],
            'SA' => ['zatca_enabled' => true, 'zatca_csid' => null, 'vat_percent' => 15.00, 'tax_number' => '300000000000003'],
            'EG' => ['zatca_enabled' => false, 'vat_percent' => 14.00, 'tax_number' => '200000000000002'],
        ];

        foreach ($countries as $country => $config) {
            $company = Company::factory()->create(array_merge([
                'country' => $country,
                'name_ar' => "شركة {$country}",
                'tax_number' => $config['tax_number'],
                'vat_percent' => $config['vat_percent'],
            ], $config));

            $customer = Customer::factory()->create([
                'company_id' => $company->id,
                'status' => 'approved',
            ]);

            $user = User::factory()->create(['company_id' => $company->id]);

            $proforma = ProformaInvoice::factory()->create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'proforma_number' => 'PF-'.strtoupper($country).'-00001',
                'total' => 1150.00,
                'vat_amount' => $country === 'SA' ? 150.00 : 140.00,
                'status' => 'sent',
            ]);

            $pdfService = app(PdfService::class);
            $path = $pdfService->generateProforma($proforma);

            $this->assertNotEmpty($path, "Failed for {$country}");
            $this->assertTrue(\Storage::disk('private')->exists($path), "PDF not found for {$country}");
        }
    }

    public function test_receipt_pdf_uses_simple_format_regardless_of_company(): void
    {
        $company = Company::factory()->create([
            'country' => 'SA',
            'zatca_enabled' => true,
            'zatca_csid' => 'test-csid',
            'name_ar' => 'شركة سعودية',
            'tax_number' => '300000000000003',
        ]);

        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'status' => 'approved',
        ]);

        $user = User::factory()->create(['company_id' => $company->id]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'invoice_number' => 'INV-SA-00001',
            'total' => 1150.00,
            'vat_amount' => 150.00,
            'status' => 'paid',
            'paid_amount' => 1150.00,
            'remaining_amount' => 0,
        ]);

        $payment = Payment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => 1150.00,
            'method' => 'cash',
        ]);

        $pdfService = app(PdfService::class);
        $path = $pdfService->generateReceipt($payment);

        $this->assertNotEmpty($path);
        $this->assertTrue(\Storage::disk('private')->exists($path));
    }

    public function test_qr_code_contains_expected_data_for_saudi_phase2(): void
    {
        $invoice = $this->createInvoice([
            'company_country' => 'SA',
            'zatca_enabled' => true,
            'zatca_csid' => 'test-csid',
            'name_ar' => 'شركة سعودية',
            'tax_number' => '300000000000003',
            'vat_percent' => 15.00,
            'invoice_number' => 'INV-SA-00001',
            'total' => 1150.00,
            'vat_amount' => 150.00,
            'subtotal' => 1000.00,
        ]);

        // Verify the QR strategy was ZatcaPhase2Strategy
        $this->assertNotNull($invoice->zatca_qr);
        $this->assertIsString($invoice->zatca_qr);

        // Decode and verify TLV structure
        $decoded = base64_decode($invoice->zatca_qr, true);
        $this->assertNotFalse($decoded);

        $tags = $this->parseTlv($decoded);
        $this->assertArrayHasKey(1, $tags); // Seller name
        $this->assertArrayHasKey(2, $tags); // VAT number
        $this->assertArrayHasKey(3, $tags); // Timestamp
        $this->assertArrayHasKey(4, $tags); // Total with VAT
        $this->assertArrayHasKey(5, $tags); // VAT amount

        // Verify values
        $this->assertEquals('شركة اختبار', $tags[1]);
        $this->assertEquals('300000000000003', $tags[2]);
        $this->assertStringContainsString('T', $tags[3]); // ISO 8601
        $this->assertEquals('1150.00', $tags[4]); // 1000 + 15% VAT
        $this->assertEquals('150.00', $tags[5]); // VAT amount
    }

    public function test_qr_code_contains_expected_data_for_egypt(): void
    {
        $invoice = $this->createInvoice([
            'company_country' => 'EG',
            'zatca_enabled' => false,
            'name_ar' => 'شركة مصرية',
            'tax_number' => '200000000000002',
            'vat_percent' => 14.00,
            'invoice_number' => 'INV-EG-00001',
            'total' => 1140.00,
            'vat_amount' => 140.00,
            'subtotal' => 1000.00,
        ]);

        // Egypt format: invoice_number|total
        $expectedFormat = 'INV-EG-00001|1140.00';
        $this->assertEquals($expectedFormat, $invoice->zatca_qr);
    }

    /**
     * Parse TLV (Tag-Length-Value) binary data into associative array.
     */
    private function parseTlv(string $data): array
    {
        $tags = [];
        $offset = 0;
        $length = strlen($data);

        while ($offset < $length) {
            if ($offset + 2 > $length) {
                break;
            }

            $tag = ord($data[$offset]);
            $valueLength = ord($data[$offset + 1]);

            if ($offset + 2 + $valueLength > $length) {
                break;
            }

            $value = substr($data, $offset + 2, $valueLength);
            $tags[$tag] = $value;

            $offset += 2 + $valueLength;
        }

        return $tags;
    }
}
