<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\Contracts\PushGateway;

class PushService
{
    public function __construct(private readonly PushGateway $gateway) {}

    /** @param array<string, mixed> $payload */
    public function send(User $user, array $payload): int
    {
        $delivered = 0;
        PushSubscription::query()->where('user_id', $user->id)->orderBy('id')
            ->chunkById(100, function ($subscriptions) use ($payload, &$delivered): void {
                foreach ($subscriptions as $subscription) {
                    $delivered += $this->gateway->deliver($subscription, $payload) ? 1 : 0;
                }
            });

        return $delivered;
    }
}
