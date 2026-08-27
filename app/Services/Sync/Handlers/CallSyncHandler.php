<?php

namespace App\Services\Sync\Handlers;

use App\Models\User;
use App\Services\CallService;

class CallSyncHandler extends AbstractRepWriteHandler
{
    public function __construct(private readonly CallService $calls) {}

    public function type(): string
    {
        return 'call';
    }

    public function handle(User $rep, array $payload, ?string $idempotencyKey = null): array
    {
        $data = $this->validated($payload, [
            'customer_id' => ['required', 'integer'],
            'contact_id' => ['nullable', 'integer'],
            'direction' => ['nullable', 'string', 'in:outbound,inbound'],
            'duration_seconds' => ['required', 'integer', 'min:0'],
            'outcome' => ['required', 'string', 'in:reached,no_answer,busy,left_voicemail'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'called_at' => ['nullable', 'date'],
        ]);

        $this->assertCustomerInCompany($rep, (int) $data['customer_id']);

        $call = $this->calls->create(
            companyId: $rep->activeCompanyId(),
            userId: $rep->id,
            data: $data,
        );

        return ['call_id' => $call->id];
    }
}
