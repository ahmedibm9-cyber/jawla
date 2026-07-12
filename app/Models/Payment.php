<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'company_id', 'customer_id', 'user_id', 'invoice_id', 'visit_id',
        'amount', 'method', 'collected_at', 'notes',
    ];

    protected $casts = ['amount' => 'decimal:2', 'collected_at' => 'datetime'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function visit(): BelongsTo { return $this->belongsTo(Visit::class); }
}
