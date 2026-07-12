<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'route_id', 'code', 'name_ar', 'name_en', 'phone',
        'address', 'latitude', 'longitude', 'credit_limit', 'balance', 'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'credit_limit' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function route(): BelongsTo { return $this->belongsTo(Route::class); }
    public function visits(): HasMany { return $this->hasMany(Visit::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function returns(): HasMany { return $this->hasMany(ReturnRecord::class); }
}
