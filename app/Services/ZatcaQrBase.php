<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Services\Contracts\QrStrategy;

abstract class ZatcaQrBase implements QrStrategy
{
    abstract protected function phaseLabel(): string;

    public function generate(object $document): string
    {
        if ($document instanceof Invoice) {
            return $this->generateInvoiceQr($document);
        }

        if ($document instanceof ProformaInvoice) {
            return $this->generateProformaQr($document);
        }

        throw new \InvalidArgumentException("Unsupported document type for {$this->phaseLabel()}");
    }

    private function generateInvoiceQr(Invoice $invoice): string
    {
        $snapshot = $invoice->snapshot_company;
        $totals = $invoice->snapshot_totals;

        $sellerName = $snapshot['name_ar'] ?? $invoice->company->name_ar ?? '';
        $vatNumber = $snapshot['tax_number'] ?? $invoice->company->tax_number ?? '';
        $timestamp = $invoice->issued_at->toIso8601String();
        $totalWithVat = (string) number_format((float) ($totals['total'] ?? $invoice->total), 2, '.', '');
        $vatAmount = (string) number_format((float) ($totals['vat_amount'] ?? $invoice->vat_amount), 2, '.', '');

        return $this->encodeTlv([
            1 => $sellerName,
            2 => $vatNumber,
            3 => $timestamp,
            4 => $totalWithVat,
            5 => $vatAmount,
        ]);
    }

    private function generateProformaQr(ProformaInvoice $proforma): string
    {
        $sellerName = $proforma->company->name_ar ?? '';
        $vatNumber = $proforma->company->tax_number ?? '';
        $timestamp = $proforma->posting_date?->toIso8601String() ?? now()->toIso8601String();
        $totalWithVat = (string) number_format((float) $proforma->total, 2, '.', '');
        $vatAmount = (string) number_format((float) $proforma->vat_amount, 2, '.', '');

        return $this->encodeTlv([
            1 => $sellerName,
            2 => $vatNumber,
            3 => $timestamp,
            4 => $totalWithVat,
            5 => $vatAmount,
        ]);
    }

    /** @param array<int, string> $fields */
    private function encodeTlv(array $fields): string
    {
        $tlv = '';
        foreach ($fields as $tag => $value) {
            $valueBytes = mb_convert_encoding($value, 'UTF-8');
            $length = strlen($valueBytes);

            if ($length > 255) {
                throw new \RuntimeException("TLV field $tag exceeds 255 bytes");
            }

            $tlv .= chr($tag).chr($length).$valueBytes;
        }

        return base64_encode($tlv);
    }
}
