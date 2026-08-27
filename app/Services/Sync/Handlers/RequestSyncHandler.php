<?php

namespace App\Services\Sync\Handlers;

use App\Models\User;
use App\Services\RequestService;

class RequestSyncHandler extends AbstractRepWriteHandler
{
    public function __construct(private readonly RequestService $requests) {}

    public function type(): string
    {
        return 'request';
    }

    public function handle(User $rep, array $payload, ?string $idempotencyKey = null): array
    {
        $data = $this->validated($payload, [
            'type' => ['required', 'string', 'in:discount,leave,price_override,other'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $request = $this->requests->create(
            companyId: $rep->activeCompanyId(),
            userId: $rep->id,
            data: $data,
        );

        return ['request_id' => $request->id];
    }
}
