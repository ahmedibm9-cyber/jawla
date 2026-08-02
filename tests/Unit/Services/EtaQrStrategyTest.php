<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Models\User;
use App\Services\EtaQrStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtaQrStrategyTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_qr_is_valid_base64_json(): void
    {
        $company = Company::factory()->create([
            'country' => 'EG',
            'name_ar' => 'شركة اختبار',
            'tax_number' => '200000000000002',
        ]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'total' => 1140.00,
            'vat_amount' => 140.00,
        ]);

        $qr = app(EtaQrStrategy::class)->generate($invoice);

        $decoded = json_decode(base64_decode($qr), true);
        $this->assertIsArray($decoded);
        $this->assertSame('شركة اختبار', $decoded['sellerName']);
        $this->assertSame('200000000000002', $decoded['taxNumber']);
        $this->assertEquals(1140.0, $decoded['invoiceTotal']);
        $this->assertEquals(140.0, $decoded['vatAmount']);
        $this->assertArrayHasKey('invoiceTimestamp', $decoded);
    }

    public function test_proforma_qr_is_valid_base64_json(): void
    {
        $company = Company::factory()->create([
            'country' => 'EG',
            'name_ar' => 'شركة اختبار',
            'tax_number' => '200000000000002',
        ]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $proforma = ProformaInvoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'total' => 570.00,
            'vat_amount' => 70.00,
        ]);

        $qr = app(EtaQrStrategy::class)->generate($proforma);

        $decoded = json_decode(base64_decode($qr), true);
        $this->assertIsArray($decoded);
        $this->assertEquals(570.0, $decoded['invoiceTotal']);
        $this->assertEquals(70.0, $decoded['vatAmount']);
    }

    public function test_unsupported_document_type_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(EtaQrStrategy::class)->generate(new \stdClass);
    }
}
