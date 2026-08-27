<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookService
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly WebhookUrlGuard $urlGuard,
        private readonly LicenseService $licenses,
    ) {}

    /** @param array<string, mixed> $payload */
    public function dispatch(int $companyId, string $eventType, array $payload): int
    {
        if (! $this->licenses->runtimeFeatureEnabled('webhooks')) {
            return 0;
        }
        $count = 0;
        WebhookEndpoint::query()->where('company_id', $companyId)->where('is_active', true)
            ->whereJsonContains('events', $eventType)->orderBy('id')
            ->chunkById(50, function ($endpoints) use ($companyId, $eventType, $payload, &$count): void {
                foreach ($endpoints as $endpoint) {
                    WebhookDelivery::create([
                        'company_id' => $companyId,
                        'webhook_endpoint_id' => $endpoint->id,
                        'event_id' => (string) Str::uuid(),
                        'event_type' => $eventType,
                        'payload' => $payload,
                        'status' => 'pending',
                        'next_retry_at' => now(),
                    ]);
                    $count++;
                }
            });

        return $count;
    }

    public function attempt(WebhookDelivery $delivery): WebhookDelivery
    {
        $claim = $this->claim($delivery->id);
        if ($claim === null) {
            return $delivery->fresh();
        }

        try {
            $target = $this->urlGuard->resolve($claim->endpoint->url);
            $body = $this->body($claim);
            $response = Http::withBody($body, 'application/json')
                ->withHeaders($this->headers($claim, $body))
                ->withOptions(['allow_redirects' => false, 'curl' => [CURLOPT_RESOLVE => [$this->curlResolution($target)]]])
                ->timeout($claim->endpoint->timeout_seconds)
                ->post($claim->endpoint->url);

            return $this->finish($claim, [
                'successful' => $response->successful(),
                'http_status' => $response->status(),
                'response_excerpt' => Str::limit($response->body(), 2000),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->finish($claim, [
                'successful' => false,
                'error' => 'Webhook delivery failed; inspect the server log with the event id.',
            ]);
        }
    }

    public function deliverDue(int $limit = 50): int
    {
        if (! $this->licenses->runtimeFeatureEnabled('webhooks')) {
            return 0;
        }

        $ids = WebhookDelivery::withoutGlobalScopes()
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->where(function ($query): void {
                $query->whereIn('status', ['pending', 'failed'])->where('next_retry_at', '<=', now())
                    ->orWhere(fn ($stale) => $stale->where('status', 'processing')->where('leased_at', '<', now()->subMinutes(5)));
            })
            ->orderBy('next_retry_at')->limit($limit)->pluck('id');

        foreach ($ids as $id) {
            $delivery = WebhookDelivery::withoutGlobalScopes()->findOrFail($id);
            $this->attempt($delivery);
        }

        return $ids->count();
    }

    public function rotateSecret(WebhookEndpoint $endpoint, User $actor): string
    {
        throw_unless($actor->can('integrations.manage') && $actor->hasCompanyAccess((int) $endpoint->company_id), new AuthorizationException(
            'You cannot rotate this webhook secret.',
        ));
        $secret = base64_encode(random_bytes(32));
        $endpoint->update(['secret' => $secret, 'secret_rotated_at' => now()]);
        Activity::log('webhook_secret_rotated', $endpoint, "Webhook secret rotated for endpoint #{$endpoint->id}");

        return $secret;
    }

    private function claim(int $deliveryId): ?WebhookDelivery
    {
        return DB::transaction(function () use ($deliveryId): ?WebhookDelivery {
            $delivery = WebhookDelivery::withoutGlobalScopes()->with('endpoint')->lockForUpdate()->findOrFail($deliveryId);
            if ($delivery->status === 'succeeded' || $delivery->attempts >= self::MAX_ATTEMPTS) {
                return null;
            }
            if ($delivery->status === 'processing' && $delivery->leased_at?->gte(now()->subMinutes(5))) {
                return null;
            }

            $delivery->update(['status' => 'processing', 'lease_token' => (string) Str::uuid(), 'leased_at' => now()]);

            return $delivery->fresh('endpoint');
        });
    }

    private function body(WebhookDelivery $delivery): string
    {
        return json_encode([
            'id' => $delivery->event_id,
            'type' => $delivery->event_type,
            'occurred_at' => $delivery->created_at->toIso8601String(),
            'data' => $delivery->payload,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @return array<string, string> */
    private function headers(WebhookDelivery $delivery, string $body): array
    {
        return [
            'X-Jawla-Event' => (string) $delivery->event_id,
            'X-Jawla-Signature' => 'sha256='.hash_hmac('sha256', $body, $delivery->endpoint->secret),
        ];
    }

    /** @param array{host:string,port:int,ip:string} $target */
    private function curlResolution(array $target): string
    {
        $ip = str_contains($target['ip'], ':') ? "[{$target['ip']}]" : $target['ip'];

        return "{$target['host']}:{$target['port']}:{$ip}";
    }

    /** @param array{successful:bool,http_status?:int,response_excerpt?:string,error?:string} $outcome */
    private function finish(WebhookDelivery $delivery, array $outcome): WebhookDelivery
    {
        return DB::transaction(function () use ($delivery, $outcome): WebhookDelivery {
            $locked = WebhookDelivery::withoutGlobalScopes()->lockForUpdate()->findOrFail($delivery->id);
            if (! hash_equals((string) $locked->lease_token, (string) $delivery->lease_token)) {
                return $locked;
            }

            $attempts = $locked->attempts + 1;
            $successful = $outcome['successful'];
            $locked->update([
                'attempts' => $attempts,
                'status' => $successful ? 'succeeded' : 'failed',
                'http_status' => $outcome['http_status'] ?? null,
                'response_excerpt' => $outcome['response_excerpt'] ?? null,
                'last_error' => $successful ? null : ($outcome['error'] ?? 'Remote endpoint returned a non-success status.'),
                'delivered_at' => $successful ? now() : null,
                'next_retry_at' => $successful || $attempts >= self::MAX_ATTEMPTS ? null : now()->addMinutes(5 * (2 ** ($attempts - 1))),
                'lease_token' => null,
                'leased_at' => null,
            ]);

            return $locked->fresh();
        });
    }
}
