<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alarm extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id', 'type', 'reference_type', 'reference_id',
        'title', 'description', 'severity', 'is_read', 'read_by', 'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function reads(): HasMany
    {
        return $this->hasMany(AlarmRead::class);
    }

    public function isAcknowledgedBy(int $userId): bool
    {
        return $this->reads()->where('user_id', $userId)->where('acknowledged', true)->exists();
    }
}
