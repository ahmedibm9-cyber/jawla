<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Services\ZatcaPhase2Strategy;
use Tests\TestCase;

class ZatcaPhase2StrategyTest extends TestCase
{
    private ZatcaPhase2Strategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new ZatcaPhase2Strategy();
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
    public function test_tlv_contains_all_five_tags_for_phase2(): void
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

        $tags = $this->parseTlv($decoded);

        // Verify all 5 tags exist (same as Phase 1)
        $this->assertArrayHasKey(1, $tags, 'Tag 1 (Seller Name) missing');
        $this->assertArrayHasKey(2, $tags, 'Tag 2 (VAT Number) missing');
        $this->assertArrayHasKey(3, $tags, 'Tag 3 (Timestamp) missing');
        $this->assertArrayHasKey(4, $tags, 'Tag 4 (Total with VAT) missing');
        $this->assertArrayHasKey(5, $tags, 'Tag 5 (VAT Amount) missing');

        // Verify tag values
        $this->assertEquals('شركة الاختبار', $tags[1]);
        $this->assertEquals('300000000000003', $tags[2]);
        $this->assertStringContainsString('T', $tags[3]); // ISO 8601
        $this->assertEquals('1150.00', $tags[4]);
        $this->assertEquals('150.00', $tags[5]);
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
    public function test_throws_on_unsupported_document_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported document type');

        $this->strategy->generate(new \stdClass());
    }

    /** @test */
    public function test_format_matches_phase1_structure(): void
    {
        // Phase 2 currently has same TLV structure as Phase 1
        // The difference is in cryptographic stamp which would be added
        // to the invoice model, not in the QR code itself

        $company = new Company([
            'name_ar' => 'شركة الاختبار',
            'tax_number' => '300000000000003',
        ]);

        $invoice = Invoice::factory()->make([
            'company_id' => 1,
            'total' => 1000.00,
            'vat_amount' => 150.00,
            'issued_at' => now(),
        ]);
        $invoice->setRelation('company', $company);

        $phase1 = new \App\Services\ZatcaPhase1Strategy();
        $phase2 = new \App\Services\ZatcaPhase2Strategy();

        $qr1 = $phase1->generate($invoice);
        $qr2 = $phase2->generate($invoice);

        // Both should produce identical TLV for now
        $this->assertEquals($qr1, $qr2);
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