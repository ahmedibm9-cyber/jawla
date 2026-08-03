<?php

namespace App\Services\Contracts;

use App\Models\PushSubscription;

interface PushGateway
{
    public function deliver(PushSubscription $subscription, array $payload): bool;
}
