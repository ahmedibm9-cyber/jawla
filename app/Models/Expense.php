<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property int|null $work_session_id
 * @property string $category
 * @property string $amount
 * @property string|null $note
 * @property Carbon|null $spent_at
 * @property Carbon|null $posting_date
 * @property Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Expense extends Model
{
    use AppendOnly;
    use BelongsToCompany;

    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'work_session_id', 'category',
        'amount', 'note', 'spent_at', 'posting_date',
        'cancelled_at', 'cancelled_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'spent_at' => 'datetime',
        'posting_date' => 'date',
        'cancelled_at' => 'datetime',
    ];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<WorkSession, $this> */
    public function workSession(): BelongsTo
    {
        return $this->belongsTo(WorkSession::class);
    }
}
