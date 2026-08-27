<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $webhook_endpoint_id
 * @property int $event_id
 * @property string $event_type
 * @property array<string, mixed>|null $payload
 * @property string $status
 * @property string|null $lease_token
 * @property Carbon|null $leased_at
 * @property int $attempts
 * @property int|null $http_status
 * @property string|null $response_excerpt
 * @property string|null $last_error
 * @property Carbon|null $delivered_at
 * @property Carbon|null $next_retry_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WebhookDelivery extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'webhook_endpoint_id', 'event_id', 'event_type', 'payload', 'status', 'lease_token', 'leased_at', 'attempts', 'http_status', 'response_excerpt', 'last_error', 'delivered_at', 'next_retry_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'leased_at' => 'datetime', 'delivered_at' => 'datetime', 'next_retry_at' => 'datetime'];
    }

    /** @return BelongsTo<WebhookEndpoint, $this> */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }
}
