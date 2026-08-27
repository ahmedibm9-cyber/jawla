<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $customer_id
 * @property int|null $customer_outlet_id
 * @property int $user_id
 * @property int $assigned_by
 * @property string $assignment_type
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CustomerAssignment extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'customer_id', 'customer_outlet_id', 'user_id', 'assigned_by', 'assignment_type', 'starts_on', 'ends_on', 'is_active'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'is_active' => 'boolean'];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<CustomerOutlet, $this> */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(CustomerOutlet::class, 'customer_outlet_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
