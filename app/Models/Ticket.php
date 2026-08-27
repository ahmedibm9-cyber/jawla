<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property int|null $customer_id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property string|null $priority
 * @property int|null $assigned_to
 * @property Carbon|null $resolved_at
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Ticket extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'customer_id',
        'title',
        'description',
        'status',
        'priority',
        'assigned_to',
        'resolved_at',
        'is_active',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<TicketStatusHistory, $this> */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class);
    }

    public function transitionTo(string $newStatus, int $userId, ?string $notes = null): void
    {
        $oldStatus = $this->status;

        $this->update(['status' => $newStatus]);

        $this->statusHistory()->create([
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $userId,
            'changed_at' => now(),
            'notes' => $notes,
        ]);

        if (in_array($newStatus, ['completed', 'cancelled', 'disabled'])) {
            $this->update(['resolved_at' => now()]);
        }
    }

    public function assign(int $assigneeId): void
    {
        $this->update(['assigned_to' => $assigneeId]);
    }

    public function isNew(): bool
    {
        return $this->status === 'new';
    }

    public function isActive(): bool
    {
        return ! in_array($this->status, ['cancelled', 'disabled']);
    }
}
