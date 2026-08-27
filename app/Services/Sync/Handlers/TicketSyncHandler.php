<?php

namespace App\Services\Sync\Handlers;

use App\Models\User;
use App\Services\TicketService;

class TicketSyncHandler extends AbstractRepWriteHandler
{
    public function __construct(private readonly TicketService $tickets) {}

    public function type(): string
    {
        return 'ticket';
    }

    public function handle(User $rep, array $payload, ?string $idempotencyKey = null): array
    {
        $data = $this->validated($payload, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'customer_id' => ['nullable', 'integer'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
        ]);

        if (! empty($data['customer_id'])) {
            $this->assertCustomerInCompany($rep, (int) $data['customer_id']);
        }

        $ticket = $this->tickets->create(
            companyId: $rep->activeCompanyId(),
            userId: $rep->id,
            data: $data,
        );

        return ['ticket_id' => $ticket->id];
    }
}
