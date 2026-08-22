<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use AppendOnly;
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'work_session_id', 'category',
        'amount', 'note', 'spent_at', 'posting_date',
        'status', 'approved_by', 'approved_at',
        'cancelled_at', 'cancelled_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'spent_at' => 'datetime',
        'posting_date' => 'date',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workSession(): BelongsTo
    {
        return $this->belongsTo(WorkSession::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
