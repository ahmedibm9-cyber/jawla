<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Services\ZatcaPhase1Strategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZatcaPhase1StrategyTest extends TestCase
{
    use RefreshDatabase;

    private ZatcaPhase1Strategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new ZatcaPhase1Strategy;
    }

    /** @test */
    public function test_generates_valid_base64_tlv_for_invoice(): void
    {
        $company = new Company([
            'name_ar' => 'شركة الاختبار',
            'tax_number' => '300000000000003',
        ]);

        $invoice = Invoice::factory()->make([
            'company_id' => 1,
            'total' => 1150.00,
            'vat_amount' => 150.00,
            'issued_at' => now()->setTimezone('Asia/Riyadh'),
        ]);
        $invoice->setRelation('company', $company);

        $qr = $this->strategy->generate($invoice);

        $this->assertIsString($qr);
        $this->assertNotEmpty($qr);

        // Verify it's valid Base64
        $decoded = base64_decode($qr, true);
        $this->assertNotFalse($decoded, 'QR should be valid Base64');
    }

    /** @test */
    public function test_tlv_contains_all_five_tags(): void
    {
        $company = new Company([
            'name_ar' => 'شركة الاختبار',
            'tax_number' => '300000000000003',
        ]);

        $invoice = Invoice::factory()->make([
            'company_id' => 1,
            'total' => 1150.00,
            'vat_amount' => 150.00,
            'issued_at' => now()->setTimezone('Asia/Riyadh'),
        ]);
        $invoice->setRelation('company', $company);

        $qr = $this->strategy->generate($invoice);
        $decoded = base64_decode($qr, true);

        // Parse TLV: tag(1 byte) + length(1 byte) + value(length bytes)
        $tags = $this->parseTlv($decoded);

        // Verify all 5 tags exist
        $this->assertArrayHasKey(1, $tags, 'Tag 1 (Seller Name) missing');
        $this->assertArrayHasKey(2, $tags, 'Tag 2 (VAT Number) missing');
        $this->assertArrayHasKey(3, $tags, 'Tag 3 (Timestamp) missing');
        $this->assertArrayHasKey(4, $tags, 'Tag 4 (Total with VAT) missing');
        $this->assertArrayHasKey(5, $tags, 'Tag 5 (VAT Amount) missing');

        // Verify tag values
        $this->assertEquals('شركة الاختبار', $tags[1], 'Seller name mismatch');
        $this->assertEquals('300000000000003', $tags[2], 'VAT number mismatch');
        $this->assertStringContainsString('T', $tags[3], 'Timestamp should be ISO 8601');
        $this->assertEquals('1150.00', $tags[4], 'Total with VAT mismatch');
        $this->assertEquals('150.00', $tags[5], 'VAT amount mismatch');
    }

    /** @test */
    public function test_generates_valid_tlv_for_proforma_invoice(): void
    {
        $company = new Company([
            'name_ar' => 'شركة الاختبار',
            'tax_number' => '300000000000003',
        ]);

        $proforma = ProformaInvoice::factory()->make([
            'company_id' => 1,
            'total' => 1150.00,
            'vat_amount' => 150.00,
            'posting_date' => today(),
        ]);
        $proforma->setRelation('company', $company);

        $qr = $this->strategy->generate($proforma);

        $this->assertIsString($qr);
        $decoded = base64_decode($qr, true);
        $this->assertNotFalse($decoded);

        $tags = $this->parseTlv($decoded);

        $this->assertArrayHasKey(1, $tags);
        $this->assertArrayHasKey(2, $tags);
        $this->assertArrayHasKey(3, $tags);
        $this->assertArrayHasKey(4, $tags);
        $this->assertArrayHasKey(5, $tags);
    }

    /** @test */
    public function test_handles_arabic_seller_name_in_utf8(): void
    {
        $company = new Company([
            'name_ar' => 'الشركة العربية للاختبار',
            'tax_number' => '300000000000003',
        ]);

        $invoice = Invoice::factory()->make([
            'company_id' => 1,
            'total' => 1000.00,
            'vat_amount' => 150.00,
            'issued_at' => now(),
        ]);
        $invoice->setRelation('company', $company);

        $qr = $this->strategy->generate($invoice);
        $decoded = base64_decode($qr, true);
        $tags = $this->parseTlv($decoded);

        // Arabic should be preserved in UTF-8
        $this->assertEquals('الشركة العربية للاختبار', $tags[1]);
    }

    /** @test */
    public function test_formats_numbers_with_two_decimal_places(): void
    {
        $company = new Company([
            'name_ar' => 'Test',
            'tax_number' => '300000000000003',
        ]);

        $invoice = Invoice::factory()->make([
            'company_id' => 1,
            'total' => 1000.5,
            'vat_amount' => 150.075,
            'issued_at' => now(),
        ]);
        $invoice->setRelation('company', $company);

        $qr = $this->strategy->generate($invoice);
        $decoded = base64_decode($qr, true);
        $tags = $this->parseTlv($decoded);

        $this->assertEquals('1000.50', $tags[4], 'Total should have 2 decimal places');
        $this->assertEquals('150.08', $tags[5], 'VAT should have 2 decimal places (rounded)');
    }

    /** @test */
    public function test_throws_on_unsupported_document_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported document type');

        $this->strategy->generate(new \stdClass);
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
                break; // Incomplete TLV
            }

            $tag = ord($data[$offset]);
            $valueLength = ord($data[$offset + 1]);

            if ($offset + 2 + $valueLength > $length) {
                break; // Truncated value
            }

            $value = substr($data, $offset + 2, $valueLength);
            $tags[$tag] = $value;

            $offset += 2 + $valueLength;
        }

        return $tags;
    }
}
