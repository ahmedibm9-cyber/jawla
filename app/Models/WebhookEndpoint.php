<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $url
 * @property string|null $secret
 * @property Carbon|null $secret_rotated_at
 * @property array<string, mixed>|null $events
 * @property bool $is_active
 * @property int|null $timeout_seconds
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WebhookEndpoint extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'url', 'secret', 'secret_rotated_at', 'events', 'is_active', 'timeout_seconds', 'created_by'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return ['secret' => 'encrypted', 'secret_rotated_at' => 'datetime', 'events' => 'array', 'is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (WebhookEndpoint $endpoint): void {
            if ($endpoint->isDirty('secret') && strlen((string) $endpoint->secret) < 32) {
                throw new \DomainException('Webhook signing secrets must contain at least 32 characters.');
            }
        });
    }

    /** @return HasMany<WebhookDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
