<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationPing extends Model
{
    use BelongsToCompany;
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workSession(): BelongsTo
    {
        return $this->belongsTo(WorkSession::class);
    }
}
