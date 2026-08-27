<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\LocationPingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property int|null $work_session_id
 * @property string $latitude
 * @property string $longitude
 * @property string $accuracy
 * @property Carbon|null $recorded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class LocationPing extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<LocationPingFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'work_session_id',
        'latitude', 'longitude', 'accuracy', 'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'accuracy' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<WorkSession, $this> */
    public function workSession(): BelongsTo
    {
        return $this->belongsTo(WorkSession::class);
    }
}
