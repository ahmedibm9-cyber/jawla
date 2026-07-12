<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSession extends Model
{
    protected $fillable = [
        'user_id', 'route_id', 'started_at', 'ended_at',
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

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function route(): BelongsTo { return $this->belongsTo(Route::class); }
    public function visits(): HasMany { return $this->hasMany(Visit::class); }
    public function expenses(): HasMany { return $this->hasMany(Expense::class); }
}
