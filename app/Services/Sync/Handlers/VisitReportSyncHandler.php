<?php

namespace App\Services\Sync\Handlers;

use App\Models\User;
use App\Models\Visit;
use App\Services\VisitReportService;

/**
 * Offline visit report → VisitReportService::submit (report row + optional
 * signature + close visit), applied exactly once on sync. The visit is loaded
 * scoped to the rep's company so a tampered visit_id cannot cross tenants.
 */
class VisitReportSyncHandler extends AbstractRepWriteHandler
{
    public function __construct(private readonly VisitReportService $reports) {}

    public function type(): string
    {
        return 'visit_report';
    }

    public function handle(User $rep, array $payload): array
    {
        $data = $this->validated($payload, [
            'visit_id' => ['required', 'integer'],
            'summary' => ['required', 'string', 'min:5'],
            'customer_feedback' => ['nullable', 'string'],
            'action_taken' => ['nullable', 'string'],
            'follow_up_needed' => ['nullable', 'boolean'],
            'follow_up_note' => ['nullable', 'string'],
            'signature' => ['nullable', 'string'],
        ]);

        $visit = Visit::query()
            ->whereHas('customer', fn ($q) => $q->where('company_id', $rep->company_id))
            ->findOrFail((int) $data['visit_id']);

        $report = $this->reports->submit($visit, [
            'summary' => $data['summary'],
            'customer_feedback' => $data['customer_feedback'] ?? null,
            'action_taken' => $data['action_taken'] ?? null,
            'follow_up_needed' => (bool) ($data['follow_up_needed'] ?? false),
            'follow_up_note' => $data['follow_up_note'] ?? null,
        ], $data['signature'] ?? null);

        return ['visit_report_id' => $report->id];
    }
}
