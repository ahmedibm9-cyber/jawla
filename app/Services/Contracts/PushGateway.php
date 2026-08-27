<?php

namespace App\Services\Contracts;

use App\Models\PushSubscription;

interface PushGateway
{
    /** @param array<string, mixed> $payload */
    public function deliver(PushSubscription $subscription, array $payload): bool;
}
