<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnRecord extends Model
{
    use SoftDeletes;

    protected $table = 'returns';

    protected $fillable = [
        'company_id', 'customer_id', 'user_id', 'visit_id', 'return_number',
        'total', 'reason', 'status', 'returned_at',
    ];

    protected $casts = ['total' => 'decimal:2', 'returned_at' => 'datetime'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function visit(): BelongsTo { return $this->belongsTo(Visit::class); }
    public function items(): HasMany { return $this->hasMany(ReturnItem::class, 'return_id'); }
}
