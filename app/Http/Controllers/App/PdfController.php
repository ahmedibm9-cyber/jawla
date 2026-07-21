<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProformaInvoice;
use App\Services\PdfService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PdfController extends Controller
{
    public function proforma(ProformaInvoice $proforma, PdfService $pdf): Response
    {
        abort_unless(
            $proforma->company_id === auth()->user()->company_id
            && $proforma->user_id === auth()->id(),
            403
        );

        $path = $pdf->generateProforma($proforma);

        return $this->download($path, "proforma_{$proforma->proforma_number}.pdf");
    }

    public function receipt(Payment $payment, PdfService $pdf): Response
    {
        abort_unless(
            $payment->company_id === auth()->user()->company_id
            && $payment->user_id === auth()->id(),
            403
        );

        $path = $pdf->generateReceipt($payment);

        return $this->download($path, "receipt_{$payment->id}.pdf");
    }

    public function invoice(Invoice $invoice, PdfService $pdf): Response
    {
        abort_unless(
            $invoice->company_id === auth()->user()->company_id
            && $invoice->user_id === auth()->id(),
            403
        );

        $path = $pdf->generateInvoice($invoice);

        return $this->download($path, "invoice_{$invoice->invoice_number}.pdf");
    }

    private function download(string $path, string $filename): Response
    {
        abort_unless(Storage::disk('private')->exists($path), 404);

        return response(Storage::disk('private')->get($path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }
}
