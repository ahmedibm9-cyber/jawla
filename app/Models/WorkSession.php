<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\WorkSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property int|null $route_id
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 * @property string|null $start_latitude
 * @property string|null $start_longitude
 * @property string|null $end_latitude
 * @property string|null $end_longitude
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WorkSession extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<WorkSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'route_id', 'started_at', 'ended_at',
        'start_latitude', 'start_longitude', 'end_latitude', 'end_longitude',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'start_latitude' => 'decimal:7',
        'start_longitude' => 'decimal:7',
        'end_latitude' => 'decimal:7',
        'end_longitude' => 'decimal:7',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Route, $this> */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /** @return HasMany<Visit, $this> */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /** @return HasMany<Expense, $this> */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
