<?php

namespace App\Services;

use App\Models\Visit;
use App\Models\VisitReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Persists a visit report (summary + optional signature) and closes the visit.
 * Extracted from VisitFlow so the same path is reused by the offline sync
 * handler — a report captured offline replays through exactly this method.
 */
class VisitReportService
{
    /**
     * @param  array{summary: string, customer_feedback?: ?string, action_taken?: ?string, follow_up_needed?: bool, follow_up_note?: ?string}  $data
     * @param  string|null  $signatureDataUrl  base64 data URL (e.g. "data:image/png;base64,....")
     */
    public function submit(Visit $visit, array $data, ?string $signatureDataUrl = null): VisitReport
    {
        return DB::transaction(function () use ($visit, $data, $signatureDataUrl): VisitReport {
            $signaturePath = null;

            if ($signatureDataUrl) {
                $parts = explode(',', $signatureDataUrl, 2);
                $meta = $parts[0] ?? '';
                $raw = base64_decode($parts[1] ?? '', true);
                throw_unless($raw !== false && $raw !== '', new \InvalidArgumentException('Invalid signature data.'));
                throw_unless(strlen($raw) <= 5 * 1024 * 1024, new \InvalidArgumentException('Signature exceeds 5MB limit.'));

                // Verify actual MIME type via magic bytes, not just the data URL prefix
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($raw);
                throw_unless(in_array($mimeType, ['image/png', 'image/jpeg']), new \InvalidArgumentException('Signature must be PNG or JPEG.'));

                $ext = $mimeType === 'image/png' ? 'png' : 'jpg';
                $signaturePath = 'signatures/'.$visit->id.'_'.Str::random(20).'.'.$ext;
                Storage::disk(config('filesystems.storage_disk'))->put($signaturePath, $raw);
            }

            $report = VisitReport::create([
                'visit_id' => $visit->id,
                'summary' => $data['summary'],
                'customer_feedback' => $data['customer_feedback'] ?? null,
                'action_taken' => $data['action_taken'] ?? null,
                'follow_up_needed' => $data['follow_up_needed'] ?? false,
                'follow_up_note' => $data['follow_up_note'] ?? null,
                'submitted_at' => now(),
                'signature_path' => $signaturePath,
            ]);

            $visit->update(['status' => 'closed']);
            $visit->dailyVisitAssignment?->update(['status' => 'completed']);

            return $report;
        });
    }
}
