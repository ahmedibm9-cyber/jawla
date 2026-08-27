<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\AlarmFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property string $type
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string $title
 * @property string|null $description
 * @property string $severity
 * @property bool $is_read
 * @property int|null $read_by
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Alarm extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<AlarmFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'type', 'reference_type', 'reference_id',
        'title', 'description', 'severity', 'is_read', 'read_by', 'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /** @return HasMany<AlarmRead, $this> */
    public function reads(): HasMany
    {
        return $this->hasMany(AlarmRead::class);
    }

    public function isAcknowledgedBy(int $userId): bool
    {
        return $this->reads()->where('user_id', $userId)->where('acknowledged', true)->exists();
    }
}
