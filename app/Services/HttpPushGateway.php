<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Services\Contracts\PushGateway;
use Illuminate\Support\Facades\Http;

class HttpPushGateway implements PushGateway
{
    public function deliver(PushSubscription $subscription, array $payload): bool
    {
        $request = Http::acceptJson()->asJson()->timeout(10)->retry(3, 1000);
        $token = (string) config('jawla.push.gateway_token');
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->post((string) config('jawla.push.gateway_url'), [
            'subscription' => [
                'endpoint' => $subscription->endpoint,
                'keys' => ['p256dh' => $subscription->p256dh, 'auth' => $subscription->auth],
            ],
            'payload' => $payload,
        ]);

        if (in_array($response->status(), [404, 410], true)) {
            $subscription->delete();
        }

        return $response->successful();
    }
}
