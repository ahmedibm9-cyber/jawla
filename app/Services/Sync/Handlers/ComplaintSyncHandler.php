<?php

namespace App\Services\Sync\Handlers;

use App\Models\User;
use App\Services\ComplaintService;

/**
 * Offline complaint → ComplaintService::log (record + tri-role manager
 * notification), applied exactly once on sync.
 */
class ComplaintSyncHandler extends AbstractRepWriteHandler
{
    public function __construct(private readonly ComplaintService $complaints) {}

    public function type(): string
    {
        return 'complaint';
    }

    public function handle(User $rep, array $payload, ?string $idempotencyKey = null): array
    {
        $data = $this->validated($payload, [
            'customer_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:non_conforming_materials,delivery_issue,quality_issue,pricing_issue,other'],
            'description' => ['required', 'string', 'min:5'],
            'visit_id' => ['nullable', 'integer'],
        ]);

        $this->assertCustomerInCompany($rep, (int) $data['customer_id']);

        $complaint = $this->complaints->log(
            companyId: $rep->activeCompanyId(),
            userId: $rep->id,
            customerId: (int) $data['customer_id'],
            type: $data['type'],
            description: $data['description'],
            visitId: isset($data['visit_id']) ? (int) $data['visit_id'] : null,
        );

        return ['complaint_id' => $complaint->id];
    }
}
