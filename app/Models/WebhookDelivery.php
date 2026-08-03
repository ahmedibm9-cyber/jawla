<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'webhook_endpoint_id', 'event_id', 'event_type', 'payload', 'status', 'lease_token', 'leased_at', 'attempts', 'http_status', 'response_excerpt', 'last_error', 'delivered_at', 'next_retry_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'leased_at' => 'datetime', 'delivered_at' => 'datetime', 'next_retry_at' => 'datetime'];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }
}
