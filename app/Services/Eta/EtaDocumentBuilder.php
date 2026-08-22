<?php

namespace App\Services\Eta;

use App\Models\Invoice;

/**
 * Maps a Jawla Invoice to an ETA-document-shaped array (aligned to the ETA
 * document model v1.0: issuer / receiver / invoiceLines / taxTotals).
 *
 * NOTE: field mapping is complete against Jawla's data model, but the exact
 * ETA schema (item codes, tax subtypes, activity code, unit types) must be
 * validated against the official ETA SDK before go-live. This builder is pure
 * and fully unit-testable; it performs no I/O and no signing.
 */
class EtaDocumentBuilder
{
    public function build(Invoice $invoice): array
    {
        $invoice->loadMissing(['company', 'customer', 'items.product']);
        $company = $invoice->company;
        $customer = $invoice->customer;

        $lines = $invoice->items->map(function ($item) use ($invoice, $company): array {
            $net = (float) $item->line_total;
            $vatRate = (float) ($invoice->company->vat_percent ?? 0);
            $vatAmount = round($net * $vatRate / 100, 2);

            return [
                'description' => $item->product?->name_ar ?? $item->product?->name_en ?? '',
                'itemType' => 'EGS', // EGS = Egyptian goods/services code scheme
                'itemCode' => $item->product?->sku ?? (string) $item->product_id,
                'unitType' => 'EA',
                'quantity' => (float) $item->quantity,
                'unitValue' => [
                    'currencySold' => $company->currency ?? 'EGP',
                    'amountEGP' => (float) $item->unit_price,
                ],
                'salesTotal' => $net,
                'netTotal' => $net,
                'total' => round($net + $vatAmount, 2),
                'taxableItems' => [[
                    'taxType' => 'T1', // T1 = Value Added Tax
                    'amount' => $vatAmount,
                    'subType' => 'V009',
                    'rate' => $vatRate,
                ]],
            ];
        })->all();

        return [
            'issuer' => [
                'type' => 'B', // Business
                'id' => $company->tax_number,
                'name' => $company->name_en ?: $company->name_ar,
                'address' => [
                    'country' => $company->country ?? 'EG',
                    'regionCity' => $company->address ?? '',
                ],
            ],
            'receiver' => [
                'type' => 'P', // Default to Person; B2B with tax ID needs 'B' — add customer.tax_number column to enable
                'id' => $customer?->code,
                'name' => $customer?->name_en ?: $customer?->name_ar,
            ],
            'documentType' => 'I', // Invoice
            'documentTypeVersion' => (string) config('eta.document_type_version', '1.0'),
            'dateTimeIssued' => ($invoice->issued_at ?? $invoice->created_at)->toIso8601String(),
            'taxpayerActivityCode' => $company->eta_taxpayer_activity_code ?? config('eta.taxpayer_activity_code', ''),
            'internalID' => $invoice->invoice_number,
            'invoiceLines' => $lines,
            'totalDiscountAmount' => 0.0,
            'totalSalesAmount' => (float) $invoice->subtotal,
            'netAmount' => (float) $invoice->subtotal,
            'taxTotals' => [[
                'taxType' => 'T1',
                'amount' => (float) $invoice->vat_amount,
            ]],
            'totalAmount' => (float) $invoice->total,
        ];
    }
}
