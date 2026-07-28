<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProformaInvoice;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Generator;

/**
 * Generates PDF documents for invoices, proformas, and receipts. Each document
 * method handles its own data loading and template rendering, then delegates
 * to PdfEngine for the mPDF binary generation and disk cache.
 */
class PdfService
{
    public function __construct(
        private readonly InvoiceQrService $qrService,
        private readonly PdfEngine $engine,
    ) {}

    public function generateProforma(ProformaInvoice $proforma): string
    {
        $proforma->load('items.product', 'company', 'customer');
        $qrData = $this->qrService->generateForProforma($proforma);
        $qr = $this->qrSvg($qrData);

        $proforma->update(['zatca_qr' => $qrData]);

        $html = $this->render('proforma', $proforma, $qr);

        return $this->engine->renderAndSave($html, "proforma_{$proforma->proforma_number}.pdf");
    }

    public function generateReceipt(Payment $payment): string
    {
        $filename = "receipt_{$payment->id}.pdf";
        if ($cached = $this->engine->cached($filename)) {
            return $cached;
        }

        $payment->load('invoice', 'customer', 'user');
        $qr = $this->qrSvg('RECEIPT|'.$payment->id.'|'.$payment->amount.'|'.$payment->collected_at?->format('Y-m-d H:i'));

        $html = $this->renderReceipt($payment, $qr);

        return $this->engine->renderAndSave($html, $filename);
    }

    public function generateInvoice(Invoice $invoice): string
    {
        $filename = "invoice_{$invoice->invoice_number}.pdf";
        if ($cached = $this->engine->cached($filename)) {
            return $cached;
        }

        $hasSnapshot = $invoice->snapshot_company !== null;

        if ($hasSnapshot) {
            $company = (object) $invoice->snapshot_company;
            $customer = (object) $invoice->snapshot_customer;
            $sig = $invoice->user?->name;
        } else {
            $invoice->load('items.product', 'company', 'customer', 'visit.report', 'user');
            $company = $invoice->company;
            $customer = $invoice->customer;
            $sig = $invoice->visit?->report?->signature_path ?? $invoice->user?->name;
        }

        $signaturePath = $sig;
        $signatureSvg = is_string($signaturePath) && Storage::disk('private')->exists($signaturePath)
            ? '<img src="data:image/png;base64,'.base64_encode(Storage::disk('private')->get($signaturePath)).'" style="max-width:160px;max-height:60px">'
            : '<span style="font-style:italic">'.e($invoice->user?->name ?? '').'</span>';

        $qrData = $this->qrService->generateForInvoice($invoice);
        $qr = $this->qrSvg($qrData);

        $invoice->update(['zatca_qr' => $qrData]);

        $items = $hasSnapshot
            ? collect($invoice->snapshot_items)->map(fn ($s) => (object) [
                'product' => (object) ['name_ar' => $s['product_name_ar'], 'name_en' => $s['product_name_en']],
                'quantity' => $s['quantity'],
                'unit_price' => $s['unit_price'],
                'line_total' => $s['line_total'],
            ])
            : $invoice->items;

        $html = $this->renderSnapshot('invoice', $invoice, $company, $customer, $items, $qr, $signatureSvg);

        return $this->engine->renderAndSave($html, $filename);
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
        $demoBanner = $this->demoWatermark();

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
{$demoBanner}
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
        $items = $doc->items;
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
            ? e($doc->bankAccount?->bank_name ?? '').' · '.e($doc->bankAccount?->iban ?? $doc->bankAccount?->account_number ?? '')
            : e($company?->bank_name ?? '').' · '.e($company?->bank_iban ?? '');

        $number = $type === 'proforma' ? $doc->proforma_number : $doc->invoice_number;
        $date = $doc->posting_date?->format('Y-m-d') ?? '';
        $demoBanner = $this->demoWatermark();

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
{$demoBanner}
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

    private function renderSnapshot(string $type, $doc, object $company, object $customer, $items, string $qr, string $signature = ''): string
    {
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

        $bank = e($company->bank_name ?? '').' · '.e($company->bank_iban ?? '');
        $number = $doc->invoice_number;
        $date = $doc->posting_date?->format('Y-m-d') ?? '';
        $demoBanner = $this->demoWatermark();

        $totals = $doc->snapshot_totals ?? ['subtotal' => $doc->subtotal, 'vat_amount' => $doc->vat_amount, 'total' => $doc->total];
        $vatPercent = $totals['vat_percent'] ?? $company->vat_percent ?? '';

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
{$demoBanner}
<div class="header">
  <div>
    <h1>".e($company->name_ar ?? '')."</h1>
    <div>".e($company->address ?? '')."</div>
    <div>VAT: ".e($company->tax_number ?? '')."</div>
  </div>
  <div style=\"text-align:right\">
    <strong>{$title}</strong><br>
    {$numberLabel}: {$number}<br>
    {$dateLabel}: {$date}
  </div>
</div>
<hr>
<p><strong>{$customerLabel}:</strong> ".e($customer->name_ar ?? '')." (".e($customer->code ?? '').")<br>".e($customer->address ?? '')."</p>
<table>
  <thead><tr><th>{$productLabel}</th><th>{$qtyLabel}</th><th>{$priceLabel}</th><th>{$totalLabel}</th></tr></thead>
  <tbody>
    {$rows}
    <tr class="total-row"><td colspan="3">{$subtotalLabel}</td><td>{$totals['subtotal']}</td></tr>
    <tr class="total-row"><td colspan="3">{$vatLabel} ({$vatPercent}%)</td><td>{$totals['vat_amount']}</td></tr>
    <tr class="total-row"><td colspan="3">{$grandTotalLabel}</td><td>{$totals['total']} EGP</td></tr>
  </tbody>
</table>
<div class="bank-box"><strong>{$bankLabel}:</strong><br>{$bank}</div>
{$qr}
<div style="margin-top:24px"><strong>{$signatureLabel}:</strong><br>{$signature}</div>
</body></html>
HTML;
    }

    private function demoWatermark(): string
    {
        if (! config('jawla.is_demo')) {
            return '';
        }

        return '<div style="border:3px solid #9a3412;color:#9a3412;padding:10px;'
            .'margin-bottom:16px;text-align:center;font-size:18px;font-weight:bold">'
            .'SAMPLE — NOT A TAX INVOICE<br>'
            .'عينة — ليست فاتورة ضريبية'
            .'</div>';
    }
}
