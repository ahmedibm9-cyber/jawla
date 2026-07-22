<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\NamingSeries;
use App\Services\Contracts\DocumentNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumberSequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DocumentNumberService::class);
    }

    public function test_generates_formatted_string(): void
    {
        $company = Company::factory()->create(['abbr' => 'GPC']);

        $number = $this->service->generate('sales_invoice', $company->id);

        $this->assertMatchesRegularExpression('/^[A-Z]+-GPC-\d{4}-\d{5}-[0-9A-F]$/', $number);
    }

    public function test_returns_sequential_numbers(): void
    {
        $company = Company::factory()->create(['abbr' => 'GPC']);

        $a = $this->service->generate('sales_invoice', $company->id);
        $b = $this->service->generate('sales_invoice', $company->id);

        $this->assertNotSame($a, $b);
        $partsA = explode('-', $a);
        $partsB = explode('-', $b);
        $this->assertSame((int) $partsA[3] + 1, (int) $partsB[3]);
    }

    public function test_different_doc_types_have_independent_sequences(): void
    {
        $company = Company::factory()->create(['abbr' => 'GPC']);

        $inv = $this->service->generate('sales_invoice', $company->id);
        $pf = $this->service->generate('proforma', $company->id);

        $partsInv = explode('-', $inv);
        $partsPf = explode('-', $pf);
        $this->assertSame(1, (int) $partsInv[3]);
        $this->assertSame(1, (int) $partsPf[3]);
    }

    public function test_different_companies_have_independent_sequences(): void
    {
        $companyA = Company::factory()->create(['abbr' => 'ABC']);
        $companyB = Company::factory()->create(['abbr' => 'XYZ']);

        $a = $this->service->generate('sales_invoice', $companyA->id);
        $b = $this->service->generate('sales_invoice', $companyB->id);

        $this->assertStringContainsString('ABC', $a);
        $this->assertStringContainsString('XYZ', $b);
        $partsA = explode('-', $a);
        $partsB = explode('-', $b);
        $this->assertSame(1, (int) $partsA[3]);
        $this->assertSame(1, (int) $partsB[3]);
    }

    public function test_auto_creates_naming_series_row_when_missing(): void
    {
        $company = Company::factory()->create(['abbr' => 'GPC']);

        $this->assertDatabaseMissing('naming_series', [
            'name' => 'unknown_doc',
            'company_id' => $company->id,
        ]);

        $this->service->generate('unknown_doc', $company->id);

        $this->assertDatabaseHas('naming_series', [
            'name' => 'unknown_doc',
            'company_id' => $company->id,
            'current_number' => 1,
        ]);
    }

    public function test_respects_existing_naming_series_seed(): void
    {
        $company = Company::factory()->create(['abbr' => 'GPC']);
        NamingSeries::create([
            'name' => 'sales_invoice',
            'prefix' => 'INV',
            'series_format' => 'INV-GPC-{YYYY}-{#####}',
            'current_number' => 50,
            'pad_length' => 5,
            'company_id' => $company->id,
        ]);

        $number = $this->service->generate('sales_invoice', $company->id);

        $this->assertStringStartsWith('INV-GPC-', $number);
        $parts = explode('-', $number);
        $this->assertSame(51, (int) $parts[3]);
    }
}
