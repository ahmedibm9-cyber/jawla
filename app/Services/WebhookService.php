<?php

namespace App\Services;

use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookService
{
    /** @param array<string, mixed> $payload */
    public function dispatch(int $companyId, string $eventType, array $payload): int
    {
        $count = 0;
        WebhookEndpoint::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereJsonContains('events', $eventType)
            ->orderBy('id')
            ->chunkById(50, function ($endpoints) use ($companyId, $eventType, $payload, &$count): void {
                foreach ($endpoints as $endpoint) {
                    $delivery = WebhookDelivery::create([
                        'company_id' => $companyId,
                        'webhook_endpoint_id' => $endpoint->id,
                        'event_id' => (string) Str::uuid(),
                        'event_type' => $eventType,
                        'payload' => $payload,
                        'status' => 'pending',
                    ]);
                    $this->attempt($delivery);
                    $count++;
                }
            });

        return $count;
    }

    public function attempt(WebhookDelivery $delivery): WebhookDelivery
    {
        $delivery = WebhookDelivery::query()->with('endpoint')->findOrFail($delivery->id);
        throw_if($delivery->attempts >= 5, new \DomainException('Webhook delivery has exhausted its retry limit.'));
        $endpoint = $delivery->endpoint;
        $body = json_encode([
            'id' => $delivery->event_id,
            'type' => $delivery->event_type,
            'occurred_at' => $delivery->created_at->toIso8601String(),
            'data' => $delivery->payload,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $body, $endpoint->secret);

        try {
            $response = Http::withBody($body, 'application/json')
                ->withHeaders(['X-Jawla-Event' => $delivery->event_id, 'X-Jawla-Signature' => 'sha256='.$signature])
                ->timeout($endpoint->timeout_seconds)
                ->post($endpoint->url);
            $successful = $response->successful();
            $delivery->update([
                'attempts' => $delivery->attempts + 1,
                'status' => $successful ? 'succeeded' : 'failed',
                'http_status' => $response->status(),
                'response_excerpt' => Str::limit($response->body(), 2000),
                'last_error' => $successful ? null : 'Remote endpoint returned a non-success status.',
                'delivered_at' => $successful ? now() : null,
                'next_retry_at' => $successful ? null : now()->addMinutes(5 * (2 ** $delivery->attempts)),
            ]);
        } catch (\Throwable $exception) {
            $delivery->update([
                'attempts' => $delivery->attempts + 1,
                'status' => 'failed',
                'last_error' => Str::limit($exception->getMessage(), 2000),
                'next_retry_at' => now()->addMinutes(5 * (2 ** $delivery->attempts)),
            ]);
        }

        return $delivery->fresh();
    }
}
