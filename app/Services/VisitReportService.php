<?php

namespace App\Services;

use App\Models\Visit;
use App\Models\VisitReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
                $signaturePath = 'signatures/'.$visit->id.'_'.time().'.png';
                $parts = explode(',', $signatureDataUrl, 2);
                Storage::disk(config('filesystems.storage_disk'))->put($signaturePath, base64_decode($parts[1] ?? ''));
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

            return $report;
        });
    }
}
