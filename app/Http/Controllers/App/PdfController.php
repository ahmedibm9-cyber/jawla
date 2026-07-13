<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Services\PdfService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PdfController extends Controller
{
    public function proforma(ProformaInvoice $proforma, PdfService $pdf): Response
    {
        $path = $pdf->generateProforma($proforma);
        return $this->download($path, "proforma_{$proforma->proforma_number}.pdf");
    }

    public function invoice(Invoice $invoice, PdfService $pdf): Response
    {
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