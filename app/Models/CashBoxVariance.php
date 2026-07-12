<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashBoxVariance extends Model
{
    protected $table = 'cash_box_variance';

    protected $fillable = [
        'user_id', 'company_id', 'variance_date', 'expected_balance',
        'actual_balance', 'variance_amount', 'variance_type',
        'status', 'notes', 'resolved_at',
    ];

    protected $casts = [
        'variance_date' => 'date',
        'expected_balance' => 'decimal:2',
        'actual_balance' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
