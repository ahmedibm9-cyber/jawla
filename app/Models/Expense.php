<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'work_session_id', 'category',
        'amount', 'note', 'status', 'spent_at',
    ];

    protected $casts = ['amount' => 'decimal:2', 'spent_at' => 'datetime'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function workSession(): BelongsTo { return $this->belongsTo(WorkSession::class); }
}
