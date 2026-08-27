<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\SalesTargetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property string $metric
 * @property string $target_amount
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SalesTarget extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<SalesTargetFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'period_start', 'period_end',
        'metric', 'target_amount', 'created_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'target_amount' => 'decimal:2',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function coversDate(\DateTimeInterface $date): bool
    {
        return $date >= $this->period_start->startOfDay() && $date <= $this->period_end->endOfDay();
    }
}
