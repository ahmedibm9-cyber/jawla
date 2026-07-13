<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ProformaInvoice;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Generator;

class PdfService
{
    public function generateProforma(ProformaInvoice $proforma): string
    {
        $qr = $this->qrSvg($proforma->proforma_number.'|'.$proforma->total.'|'.$proforma->company?->tax_number);

        $html = $this->render('proforma', $proforma, $qr);

        return $this->toPdf($html, "proforma_{$proforma->proforma_number}.pdf");
    }

    public function generateInvoice(Invoice $invoice): string
    {
        $signaturePath = $invoice->visit?->report?->signature_path
            ?? $invoice->user?->name;
        $signatureSvg = is_string($signaturePath) && Storage::disk('private')->exists($signaturePath)
            ? '<img src="data:image/png;base64,'.base64_encode(Storage::disk('private')->get($signaturePath)).'" style="max-width:160px;max-height:60px">'
            : '<span style="font-style:italic">'.$invoice->user?->name.'</span>';

        $qr = $this->qrSvg($invoice->invoice_number.'|'.$invoice->total.'|'.$invoice->company?->tax_number);

        $html = $this->render('invoice', $invoice, $qr, $signatureSvg);

        return $this->toPdf($html, "invoice_{$invoice->invoice_number}.pdf");
    }

    private function render(string $type, $doc, string $qr, string $signature = ''): string
    {
        $company = $doc->company;
        $customer = $doc->customer;
        $items = $type === 'proforma' ? $doc->items : $doc->items;
        $lang = app()->getLocale();

        $title = $type === 'proforma'
            ? ($lang === 'ar' ? 'فاتورة مبدئية' : 'Proforma Invoice')
            : ($lang === 'ar' ? 'فاتورة ضريبية' : 'Tax Invoice');

        $numberLabel = $lang === 'ar' ? 'رقم' : 'Number';
        $customerLabel = $lang === 'ar' ? 'العميل' : 'Customer';
        $dateLabel = $lang === 'ar' ? 'التاريخ' : 'Date';
        $productLabel = $lang === 'ar' ? 'المنتج' : 'Product';
        $qtyLabel = $lang === 'ar' ? 'الكمية' : 'Qty';
        $priceLabel = $lang === 'ar' ? 'السعر' : 'Price';
        $totalLabel = $lang === 'ar' ? 'الإجمالي' : 'Total';
        $subtotalLabel = $lang === 'ar' ? 'المجموع الفرعي' : 'Subtotal';
        $vatLabel = $lang === 'ar' ? 'ضريبة القيمة المضافة' : 'VAT';
        $grandTotalLabel = $lang === 'ar' ? 'الإجمالي الكلي' : 'Grand Total';
        $bankLabel = $lang === 'ar' ? 'بيانات الحساب البنكي' : 'Bank Details';
        $signatureLabel = $lang === 'ar' ? 'التوقيع' : 'Signature';

        $dir = $lang === 'ar' ? 'rtl' : 'ltr';
        $textAlign = $lang === 'ar' ? 'right' : 'left';

        $rows = '';
        foreach ($items as $item) {
            $pName = $lang === 'ar' ? ($item->product?->name_ar ?? '') : ($item->product?->name_en ?? '');
            $rows .= "<tr><td>{$pName}</td><td>{$item->quantity}</td><td>".number_format((float)$item->unit_price, 2)."</td><td>".number_format((float)$item->line_total, 2)."</td></tr>";
        }

        $bank = $doc instanceof ProformaInvoice && $doc->company_bank_account_id
            ? ($doc->bankAccount?->bank_name.' · '.($doc->bankAccount?->iban ?? $doc->bankAccount?->account_number))
            : ($company?->bank_name.' · '.$company?->bank_iban);

        $number = $type === 'proforma' ? $doc->proforma_number : $doc->invoice_number;
        $date = $doc->posting_date?->format('Y-m-d') ?? '';

        return <<<HTML
<!doctype html>
<html lang="$lang" dir="$dir"><head><meta charset="utf-8"><style>
body{font-family:system-ui,sans-serif;margin:32px;text-align:$textAlign}
table{width:100%;border-collapse:collapse;margin-top:12px}
th,td{border:1px solid #ccc;padding:8px;text-align:$textAlign}
th{background:#4DB848;color:#fff}
.total-row td{font-weight:bold;background:#f0f0f0}
h1{color:#4DB848;margin:0}
.header{display:flex;justify-content:space-between;margin-bottom:16px}
.bank-box{background:#F5F5F4;border:1px solid #ddd;padding:12px;margin:16px 0;text-align:$textAlign}
.qr{width:100px;height:100px;margin:8px 0}
</style></head><body>
<div class="header">
  <div>
    <h1>{$company?->name_ar}</h1>
    <div>{$company?->address}</div>
    <div>VAT: {$company?->tax_number}</div>
  </div>
  <div style="text-align:right">
    <strong>{$title}</strong><br>
    {$numberLabel}: {$number}<br>
    {$dateLabel}: {$date}
  </div>
</div>
<hr>
<p><strong>{$customerLabel}:</strong> {$customer?->name_ar} ({$customer?->code})<br>{$customer?->address}</p>
<table>
  <thead><tr><th>{$productLabel}</th><th>{$qtyLabel}</th><th>{$priceLabel}</th><th>{$totalLabel}</th></tr></thead>
  <tbody>
    {$rows}
    <tr class="total-row"><td colspan="3">{$subtotalLabel}</td><td>{$doc->subtotal}</td></tr>
    <tr class="total-row"><td colspan="3">{$vatLabel} ({$company?->vat_percent}%)</td><td>{$doc->vat_amount}</td></tr>
    <tr class="total-row"><td colspan="3">{$grandTotalLabel}</td><td>{$doc->total} EGP</td></tr>
  </tbody>
</table>
<div class="bank-box"><strong>{$bankLabel}:</strong><br>{$bank}</div>
{$qr}
<div style="margin-top:24px"><strong>{$signatureLabel}:</strong><br>{$signature}</div>
</body></html>
HTML;
    }

    private function qrSvg(string $data): string
    {
        $qr = (new Generator())->size(100)->generate($data);
        return '<div class="qr">'.$qr.'</div>';
    }

    private function toPdf(string $html, string $filename): string
    {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => storage_path('app/temp'),
        ]);
        $mpdf->WriteHTML($html);
        $path = "pdfs/{$filename}";
        Storage::disk('private')->makeDirectory('pdfs');
        Storage::disk('private')->put($path, $mpdf->Output($filename, 'S'));
        return $path;
    }
}