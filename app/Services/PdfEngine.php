<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;

/**
 * Thin wrapper around mPDF and disk I/O. Exists as a seam so callers (and
 * their tests) can swap the engine for an in-memory fake without touching
 * the document-rendering code. PdfService delegates to this for every PDF
 * write; the cache lookup is here because it's part of the same disk concern.
 */
class PdfEngine
{
    public function cached(string $filename): ?string
    {
        $path = "pdfs/{$filename}";

        return Storage::disk(config('filesystems.storage_disk'))->exists($path) ? $path : null;
    }

    public function renderAndSave(string $html, string $filename): string
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => storage_path('app/temp'),
        ]);
        $mpdf->WriteHTML($html);
        $path = "pdfs/{$filename}";
        $disk = config('filesystems.storage_disk');
        Storage::disk($disk)->makeDirectory('pdfs');
        Storage::disk($disk)->put($path, $mpdf->Output($filename, 'S'));

        return $path;
    }
}
