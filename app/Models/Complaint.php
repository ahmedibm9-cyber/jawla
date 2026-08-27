<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ComplaintFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $customer_id
 * @property int $user_id
 * @property int|null $visit_id
 * @property string $complaint_type
 * @property string $description
 * @property string $status
 * @property int|null $assigned_to
 * @property string|null $resolution
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Complaint extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<ComplaintFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'customer_id', 'user_id', 'visit_id',
        'complaint_type', 'description', 'status',
        'assigned_to', 'resolution', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Visit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
