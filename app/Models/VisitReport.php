<?php

namespace App\Models;

use Database\Factories\VisitReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $visit_id
 * @property string|null $summary
 * @property string|null $customer_feedback
 * @property string|null $action_taken
 * @property bool $follow_up_needed
 * @property string|null $follow_up_note
 * @property Carbon|null $submitted_at
 * @property string|null $signature_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class VisitReport extends Model
{
    /** @use HasFactory<VisitReportFactory> */
    use HasFactory;

    protected $fillable = [
        'visit_id', 'summary', 'customer_feedback', 'action_taken',
        'follow_up_needed', 'follow_up_note', 'submitted_at', 'signature_path',
    ];

    protected $casts = [
        'follow_up_needed' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    /** @return BelongsTo<Visit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
