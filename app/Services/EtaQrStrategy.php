<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Services\Contracts\QrStrategy;

/**
 * Generates ETA-compliant QR code data for Egyptian invoices.
 * The QR payload is a base64-encoded JSON string containing the fields
 * required by the Egyptian Tax Authority for invoice verification.
 */
class EtaQrStrategy implements QrStrategy
{
    public function generate(object $document): string
    {
        if ($document instanceof Invoice) {
            return $this->generateInvoiceQr($document);
        }

        if ($document instanceof ProformaInvoice) {
            return $this->generateProformaQr($document);
        }

        throw new \InvalidArgumentException('Unsupported document type for ETA QR');
    }

    private function generateInvoiceQr(Invoice $invoice): string
    {
        $company = $invoice->company;
        $snapshot = $invoice->snapshot_company;
        $totals = $invoice->snapshot_totals;

        $payload = [
            'sellerName' => $snapshot['name_ar'] ?? $company?->name_ar ?? '',
            'taxNumber' => $snapshot['tax_number'] ?? $company?->tax_number ?? '',
            'invoiceTimestamp' => ($invoice->issued_at ?? $invoice->created_at)->toIso8601String(),
            'invoiceTotal' => (float) ($totals['total'] ?? $invoice->total),
            'vatAmount' => (float) ($totals['vat_amount'] ?? $invoice->vat_amount),
        ];

        return base64_encode(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function generateProformaQr(ProformaInvoice $proforma): string
    {
        $payload = [
            'sellerName' => $proforma->company?->name_ar ?? '',
            'taxNumber' => $proforma->company?->tax_number ?? '',
            'invoiceTimestamp' => ($proforma->posting_date ?? $proforma->created_at)->toIso8601String(),
            'invoiceTotal' => (float) $proforma->total,
            'vatAmount' => (float) $proforma->vat_amount,
        ];

        return base64_encode(json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
