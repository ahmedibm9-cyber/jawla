<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Payment;

class ThermalPrintFormatter
{
    /**
     * Build a width-safe thermal payload for an invoice.
     *
     * @return array{kind:string,qr:?string,lines:array<int,string>}
     */
    public function invoicePayload(Invoice $invoice, int $width = 32): array
    {
        $invoice->loadMissing(['company', 'customer', 'items.product']);

        $lines = [];

        $this->pushWrapped($lines, (string) ($invoice->company->name_ar ?? $invoice->company->name_en ?? config('app.name')), $width);
        if ($invoice->company->phone) {
            $this->pushWrapped($lines, (string) $invoice->company->phone, $width);
        }
        $lines[] = str_repeat('-', $width);
        $this->pushWrapped($lines, 'Invoice # '.$invoice->invoice_number, $width);
        $this->pushWrapped($lines, 'Date: '.$invoice->issued_at->format('Y-m-d H:i'), $width);
        $this->pushWrapped($lines, 'Customer: '.($invoice->customer->name_ar ?? $invoice->customer->name_en ?? '—'), $width);
        $lines[] = str_repeat('-', $width);

        foreach ($invoice->items as $item) {
            $this->pushWrapped($lines, (string) ($item->product->name_ar ?? $item->product->name_en ?? '#'.$item->product_id), $width);
            $qty = $this->formatQty((float) $item->quantity);
            $price = $this->money((float) $item->unit_price);
            $lineTotal = $this->money((float) $item->line_total);
            $this->pushWrapped($lines, "{$qty} x {$price} = {$lineTotal}", $width);
        }

        $lines[] = str_repeat('-', $width);
        $this->pushWrapped($lines, 'Subtotal: '.$this->money((float) $invoice->subtotal).' '.($invoice->company->currency ?? 'EGP'), $width);
        if ((float) $invoice->vat_amount > 0) {
            $this->pushWrapped($lines, 'VAT: '.$this->money((float) $invoice->vat_amount).' '.($invoice->company->currency ?? 'EGP'), $width);
        }
        $this->pushWrapped($lines, 'Total: '.$this->money((float) $invoice->total).' '.($invoice->company->currency ?? 'EGP'), $width);

        return [
            'kind' => 'invoice',
            'qr' => $invoice->eta_qr ?: $invoice->zatca_qr,
            'lines' => $lines,
        ];
    }

    /**
     * Build a width-safe thermal payload for a payment receipt.
     *
     * @return array{kind:string,qr:?string,lines:array<int,string>}
     */
    public function paymentPayload(Payment $payment, int $width = 32): array
    {
        $payment->loadMissing(['company', 'customer', 'invoice']);

        $lines = [];

        $this->pushWrapped($lines, (string) ($payment->company->name_ar ?? $payment->company->name_en ?? config('app.name')), $width);
        $lines[] = str_repeat('-', $width);
        $this->pushWrapped($lines, 'Payment Receipt', $width);
        if ($payment->collected_at) {
            $this->pushWrapped($lines, 'Date: '.$payment->collected_at->format('Y-m-d H:i'), $width);
        }
        $this->pushWrapped($lines, 'Customer: '.($payment->customer->name_ar ?? $payment->customer->name_en ?? '—'), $width);
        if ($payment->invoice?->invoice_number) {
            $this->pushWrapped($lines, 'Invoice: '.$payment->invoice->invoice_number, $width);
        }
        $this->pushWrapped($lines, 'Method: '.$payment->method, $width);
        $lines[] = str_repeat('-', $width);
        $this->pushWrapped($lines, 'Amount: '.$this->money((float) $payment->amount).' '.($payment->company->currency ?? 'EGP'), $width);
        if ($payment->notes) {
            $lines[] = str_repeat('-', $width);
            $this->pushWrapped($lines, 'Notes: '.$payment->notes, $width);
        }

        return [
            'kind' => 'payment',
            'qr' => null,
            'lines' => $lines,
        ];
    }

    /**
     * @param  array<int,string>  $lines
     */
    private function pushWrapped(array &$lines, string $text, int $width): void
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        if ($text === '') {
            return;
        }

        $current = '';
        foreach (preg_split('/\s+/u', $text) ?: [] as $word) {
            if ($current === '') {
                if (mb_strlen($word) <= $width) {
                    $current = $word;
                } else {
                    foreach ($this->splitLongToken($word, $width) as $chunk) {
                        $lines[] = $chunk;
                    }
                }

                continue;
            }

            $candidate = $current.' '.$word;
            if (mb_strlen($candidate) <= $width) {
                $current = $candidate;

                continue;
            }

            $lines[] = $current;

            if (mb_strlen($word) <= $width) {
                $current = $word;
            } else {
                foreach ($this->splitLongToken($word, $width) as $i => $chunk) {
                    if ($i === array_key_last($this->splitLongToken($word, $width))) {
                        $current = $chunk;
                    } else {
                        $lines[] = $chunk;
                    }
                }
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }
    }

    /**
     * @return array<int,string>
     */
    private function splitLongToken(string $text, int $width): array
    {
        $chunks = [];
        while ($text !== '') {
            $chunks[] = mb_substr($text, 0, $width);
            $text = mb_substr($text, $width);
        }

        return $chunks;
    }

    private function formatQty(float $qty): string
    {
        return rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.') ?: '0';
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
