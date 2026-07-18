<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id', 'summary', 'customer_feedback', 'action_taken',
        'follow_up_needed', 'follow_up_note', 'submitted_at', 'signature_path',
    ];

    protected $casts = [
        'follow_up_needed' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
