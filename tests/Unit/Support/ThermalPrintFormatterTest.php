<?php

namespace Tests\Unit\Support;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Support\ThermalPrintFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThermalPrintFormatterTest extends TestCase
{
    use RefreshDatabase;

    private ThermalPrintFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter = app(ThermalPrintFormatter::class);
    }

    public function test_invoice_payload_contains_key_fields_and_qr_text(): void
    {
        $company = Company::factory()->create([
            'name_ar' => 'جولة للتوزيع',
            'phone' => '01000000000',
            'currency' => 'EGP',
            'vat_percent' => 14,
        ]);
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name_ar' => 'أولاد رجب',
        ]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-GPC-2026-00042',
            'subtotal' => 2000,
            'vat_amount' => 280,
            'total' => 2280,
            'issued_at' => now()->setDate(2026, 7, 20)->setTime(14, 35),
            'eta_qr' => 'ETA-QR-42',
        ]);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name_ar' => 'جبنة بيضاء ممتازة طويلة الاسم',
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 1000,
            'line_total' => 2000,
        ]);

        $payload = $this->formatter->invoicePayload($invoice);
        $text = implode("\n", $payload['lines']);

        $this->assertSame('invoice', $payload['kind']);
        $this->assertSame('ETA-QR-42', $payload['qr']);
        $this->assertStringContainsString('INV-GPC-2026-00042', $text);
        $this->assertStringContainsString('أولاد رجب', $text);
        $this->assertStringContainsString('2280.00 EGP', $text);
        $this->assertStringContainsString('جولة للتوزيع', $text);
    }

    public function test_payment_payload_includes_invoice_reference_when_present(): void
    {
        $company = Company::factory()->create([
            'name_ar' => 'جولة',
            'currency' => 'EGP',
        ]);
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name_ar' => 'متجر الاختبار',
        ]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-9',
        ]);
        $payment = Payment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'amount' => 600,
            'method' => 'cash',
            'collected_at' => now()->setDate(2026, 7, 20)->setTime(15, 10),
        ]);

        $payload = $this->formatter->paymentPayload($payment);
        $text = implode("\n", $payload['lines']);

        $this->assertSame('payment', $payload['kind']);
        $this->assertNull($payload['qr']);
        $this->assertStringContainsString('INV-9', $text);
        $this->assertStringContainsString('600.00 EGP', $text);
        $this->assertStringContainsString('cash', strtolower($text));
    }

    public function test_payload_lines_respect_width_limit(): void
    {
        $company = Company::factory()->create([
            'name_ar' => 'شركة طويلة الاسم جدًا للاختبار',
            'currency' => 'EGP',
        ]);
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name_ar' => 'عميل طويل الاسم جدًا للاختبار',
        ]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $payload = $this->formatter->invoicePayload($invoice, width: 24);

        foreach ($payload['lines'] as $line) {
            $this->assertLessThanOrEqual(24, mb_strlen($line), $line);
        }
    }
}
