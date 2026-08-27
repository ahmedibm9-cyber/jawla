<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $company_id
 * @property string $endpoint
 * @property string $p256dh
 * @property string $auth
 * @property string|null $device_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PushSubscription extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'user_id', 'company_id', 'endpoint', 'p256dh', 'auth', 'device_id',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
