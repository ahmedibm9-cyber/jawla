<?php

namespace Tests\Unit\Services;

use App\Services\Contracts\InvoiceCalculationService as Contract;
use App\Services\Contracts\LineItemInput;
use Tests\TestCase;

class InvoiceCalculationServiceTest extends TestCase
{
    private Contract $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(Contract::class);
    }

    public function test_single_line_vat_applicable(): void
    {
        $result = $this->service->calculate(
            [new LineItemInput(2, 1000, true)],
            14,
        );

        $this->assertSame(2000.0, $result->subtotal);
        $this->assertSame(280.0, $result->vatAmount);
        $this->assertSame(2280.0, $result->total);
    }

    public function test_single_line_no_vat(): void
    {
        $result = $this->service->calculate(
            [new LineItemInput(2, 1000, false)],
            14,
        );

        $this->assertSame(2000.0, $result->subtotal);
        $this->assertSame(0.0, $result->vatAmount);
        $this->assertSame(2000.0, $result->total);
    }

    public function test_multi_line_mixed_vat(): void
    {
        $result = $this->service->calculate(
            [
                new LineItemInput(2, 1000, true),
                new LineItemInput(3, 500, false),
            ],
            14,
        );

        // subtotal = 2000 + 1500 = 3500
        $this->assertSame(3500.0, $result->subtotal);
        // VAT on vat-applicable subtotal (2000) only
        $this->assertSame(280.0, $result->vatAmount);
        $this->assertSame(3780.0, $result->total);
    }

    public function test_zero_vat_percent(): void
    {
        $result = $this->service->calculate(
            [new LineItemInput(2, 1000, true)],
            0,
        );

        $this->assertSame(2000.0, $result->subtotal);
        $this->assertSame(0.0, $result->vatAmount);
        $this->assertSame(2000.0, $result->total);
    }

    public function test_zero_qty(): void
    {
        $result = $this->service->calculate(
            [new LineItemInput(0, 1000, true)],
            14,
        );

        $this->assertSame(0.0, $result->subtotal);
        $this->assertSame(0.0, $result->vatAmount);
        $this->assertSame(0.0, $result->total);
    }

    public function test_per_line_results_contain_correct_data(): void
    {
        $result = $this->service->calculate(
            [
                new LineItemInput(2, 1000, true),
                new LineItemInput(1, 200, false),
            ],
            14,
        );

        $this->assertCount(2, $result->lines);
        $this->assertSame(2000.0, $result->lines[0]->lineTotal);
        $this->assertSame(280.0, $result->lines[0]->vatAmount);
        $this->assertTrue($result->lines[0]->vatApplicable);
        $this->assertSame(200.0, $result->lines[1]->lineTotal);
        $this->assertSame(0.0, $result->lines[1]->vatAmount);
        $this->assertFalse($result->lines[1]->vatApplicable);
    }
}
