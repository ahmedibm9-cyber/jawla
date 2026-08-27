<?php

namespace App\Models;

use App\Enums\ApprovalRequestStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property string $approvable_type
 * @property int $approvable_id
 * @property int $submitted_by
 * @property ApprovalRequestStatus $status
 * @property int $current_sequence
 * @property Carbon|null $submitted_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ApprovalRequest extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'approvable_type', 'approvable_id', 'submitted_by',
        'status', 'current_sequence', 'submitted_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApprovalRequestStatus::class,
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return HasMany<ApprovalStep, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class)->orderBy('sequence');
    }
}
