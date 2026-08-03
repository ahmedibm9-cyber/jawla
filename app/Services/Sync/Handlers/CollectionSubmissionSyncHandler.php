<?php

namespace App\Services\Sync\Handlers;

use App\Models\User;
use App\Services\CollectionSubmissionService;

class CollectionSubmissionSyncHandler extends AbstractRepWriteHandler
{
    public function __construct(private readonly CollectionSubmissionService $collections) {}

    public function type(): string
    {
        return 'collection_submission';
    }

    public function handle(User $rep, array $payload, ?string $idempotencyKey = null): array
    {
        $data = $this->validated($payload, [
            'customer_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,cheque,transfer,other'],
            'invoice_id' => ['nullable', 'integer'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $this->assertCustomerInCompany($rep, (int) $data['customer_id']);

        $submission = $this->collections->submit(
            $rep,
            (int) $data['customer_id'],
            (float) $data['amount'],
            $data['method'],
            [
                'invoice_id' => isset($data['invoice_id']) ? (int) $data['invoice_id'] : null,
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
            ],
        );

        return ['collection_submission_id' => $submission->id];
    }
}
