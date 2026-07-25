<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Services\EgyptQrStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EgyptQrStrategyTest extends TestCase
{
    use RefreshDatabase;

    private EgyptQrStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new EgyptQrStrategy;
    }

    /** @test */
    public function test_generates_simple_format_for_invoice(): void
    {
        $company = new Company(['country' => 'EG']);

        $invoice = Invoice::factory()->make([
            'company_id' => 1,
            'invoice_number' => 'INV-2024-001',
            'total' => 1140.00,
        ]);
        $invoice->setRelation('company', $company);

        $qr = $this->strategy->generate($invoice);

        $this->assertEquals('INV-2024-001|1140.00', $qr);
    }

    /** @test */
    public function test_generates_simple_format_for_proforma_invoice(): void
    {
        $company = new Company(['country' => 'EG']);

        $proforma = ProformaInvoice::factory()->make([
            'company_id' => 1,
            'proforma_number' => 'PF-2024-001',
            'total' => 1140.00,
        ]);
        $proforma->setRelation('company', $company);

        $qr = $this->strategy->generate($proforma);

        $this->assertEquals('PF-2024-001|1140.00', $qr);
    }

    /** @test */
    public function test_formats_total_with_two_decimal_places(): void
    {
        $company = new Company(['country' => 'EG']);

        $invoice = Invoice::factory()->make([
            'company_id' => 1,
            'invoice_number' => 'INV-001',
            'total' => 1000.5,
        ]);
        $invoice->setRelation('company', $company);

        $qr = $this->strategy->generate($invoice);

        $this->assertEquals('INV-001|1000.50', $qr);
    }

    /** @test */
    public function test_throws_on_unsupported_document_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported document type');

        $this->strategy->generate(new \stdClass);
    }
}
