<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProformaInvoice;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use SimpleSoftwareIO\QrCode\Generator;

class PdfService
{
    public function __construct(
        private readonly InvoiceQrService $qrService
    ) {}

    public function generateProforma(ProformaInvoice $proforma): string
    {
        $proforma->load('items.product', 'company', 'customer');
        $qrData = $this->qrService->generateForProforma($proforma);
        $qr = $this->qrSvg($qrData);

        // Save QR data to proforma for later reference
        $proforma->update(['zatca_qr' => $qrData]);

        $html = $this->render('proforma', $proforma, $qr);

        return $this->toPdf($html, "proforma_{$proforma->proforma_number}.pdf");
    }

    public function generateReceipt(Payment $payment): string
    {
        // Payments are immutable, so a receipt PDF never changes — serve the
        // cached render instead of rebuilding mPDF on every download.
        if ($cached = $this->cached("receipt_{$payment->id}.pdf")) {
            return $cached;
        }

        $payment->load('invoice', 'customer', 'user');
        $qr = $this->qrSvg('RECEIPT|'.$payment->id.'|'.$payment->amount.'|'.$payment->collected_at?->format('Y-m-d H:i'));

        $html = $this->renderReceipt($payment, $qr);

        return $this->toPdf($html, "receipt_{$payment->id}.pdf");
    }

    /**
     * Return the stored path for an already-generated PDF, or null. Used to
     * short-circuit re-rendering immutable documents (invoices, receipts) on
     * repeat downloads — the expensive mPDF pass runs once per document.
     */
    private function cached(string $filename): ?string
    {
        $path = "pdfs/{$filename}";

        return Storage::disk('private')->exists($path) ? $path : null;
    }

    public function generateInvoice(Invoice $invoice): string
    {
        // Invoices are immutable financial records (reversal is a compensating
        // transaction, never an edit), so the PDF is stable once rendered.
        if ($cached = $this->cached("invoice_{$invoice->invoice_number}.pdf")) {
            return $cached;
        }

        $invoice->load('items.product', 'company', 'customer', 'visit.report', 'user');
        $signaturePath = $invoice->visit?->report?->signature_path
            ?? $invoice->user?->name;
        $signatureSvg = is_string($signaturePath) && Storage::disk('private')->exists($signaturePath)
            ? '<img src="data:image/png;base64,'.base64_encode(Storage::disk('private')->get($signaturePath)).'" style="max-width:160px;max-height:60px">'
            : '<span style="font-style:italic">'.$invoice->user?->name.'</span>';

        $qrData = $this->qrService->generateForInvoice($invoice);
        $qr = $this->qrSvg($qrData);

        // Save QR data to invoice for later reference
        $invoice->update(['zatca_qr' => $qrData]);

        $html = $this->render('invoice', $invoice, $qr, $signatureSvg);

        return $this->toPdf($html, "invoice_{$invoice->invoice_number}.pdf");
    }

    private function renderReceipt(Payment $payment, string $qr): string
    {
        $company = $payment->company;
        $customer = $payment->customer;
        $lang = app()->getLocale();

        $title = $lang === 'ar' ? 'إيصال استلام' : 'Payment Receipt';
        $receiptLabel = $lang === 'ar' ? 'رقم الإيصال' : 'Receipt #';
        $dateLabel = $lang === 'ar' ? 'التاريخ' : 'Date';
        $customerLabel = $lang === 'ar' ? 'العميل' : 'Customer';
        $amountLabel = $lang === 'ar' ? 'المبلغ' : 'Amount';
        $methodLabel = $lang === 'ar' ? 'طريقة الدفع' : 'Payment Method';
        $invoiceLabel = $lang === 'ar' ? 'الفاتورة' : 'Invoice';
        $noteLabel = $lang === 'ar' ? 'ملاحظات' : 'Notes';
        $collectorLabel = $lang === 'ar' ? 'المحصل' : 'Collected By';

        $dir = $lang === 'ar' ? 'rtl' : 'ltr';
        $textAlign = $lang === 'ar' ? 'right' : 'left';

        $methodMap = [
            'cash' => $lang === 'ar' ? 'نقدي' : 'Cash',
            'cheque' => $lang === 'ar' ? 'شيك' : 'Cheque',
            'transfer' => $lang === 'ar' ? 'تحويل' : 'Transfer',
            'other' => $lang === 'ar' ? 'أخرى' : 'Other',
        ];

        $methodName = $methodMap[$payment->method] ?? $payment->method;

        return <<<HTML
<!doctype html>
<html lang="$lang" dir="$dir"><head><meta charset="utf-8"><style>
body{font-family:system-ui,sans-serif;margin:32px;text-align:$textAlign}
table{width:100%;border-collapse:collapse;margin-top:12px}
th,td{border:1px solid #ccc;padding:8px;text-align:$textAlign}
th{background:#6DB83B;color:#fff}
h1{color:#6DB83B;margin:0}
.header{display:flex;justify-content:space-between;margin-bottom:16px}
.qr{width:100px;height:100px;margin:8px 0}
.detail-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #eee}
</style></head><body>
<div class="header">
  <div>
    <h1>".e($company?->name_ar)."</h1>
    <div>".e($company?->address)."</div>
    <div>VAT: ".e($company?->tax_number)."</div>
  </div>
  <div style=\"text-align:right\">
    <strong>{$title}</strong><br>
    {$receiptLabel}: {$payment->id}<br>
    {$dateLabel}: {$payment->collected_at?->format('Y-m-d H:i')}
  </div>
</div>
<hr>
<div class=\"detail-row\"><span><strong>{$customerLabel}:</strong> ".e($customer?->name_ar)."</span></div>
<div class=\"detail-row\"><span><strong>{$amountLabel}:</strong> {$payment->amount}</span></div>
<div class=\"detail-row\"><span><strong>{$methodLabel}:</strong> {$methodName}</span></div>
<div class=\"detail-row\"><span><strong>{$collectorLabel}:</strong> ".e($payment->user?->name)."</span></div>
HTML.
($payment->invoice_id ? '<div class=\"detail-row\"><span><strong>'.$invoiceLabel.':</strong> '.e($payment->invoice?->invoice_number).'</span></div>' : '').
($payment->notes ? '<div class=\"detail-row\"><span><strong>'.$noteLabel.':</strong> '.e($payment->notes).'</span></div>' : '').
<<<HTML
{$qr}
</body></html>
HTML;
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
            $rows .= '<tr><td>'.e($pName)."</td><td>{$item->quantity}</td><td>".number_format((float) $item->unit_price, 2).'</td><td>'.number_format((float) $item->line_total, 2).'</td></tr>';
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
    <h1>".e($company?->name_ar)."</h1>
    <div>".e($company?->address)."</div>
    <div>VAT: ".e($company?->tax_number)."</div>
  </div>
  <div style=\"text-align:right\">
    <strong>{$title}</strong><br>
    {$numberLabel}: {$number}<br>
    {$dateLabel}: {$date}
  </div>
</div>
<hr>
<p><strong>{$customerLabel}:</strong> ".e($customer?->name_ar)." (".e($customer?->code).")<br>".e($customer?->address)."</p>
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
        $qr = (new Generator)->size(100)->generate($data);

        return '<div class="qr">'.$qr.'</div>';
    }

    private function toPdf(string $html, string $filename): string
    {
        $mpdf = new Mpdf([
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
