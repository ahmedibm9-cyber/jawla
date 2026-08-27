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
 * @property string $name
 * @property string|null $job_title
 * @property string|null $phone
 * @property string|null $email
 * @property bool $is_primary
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CustomerContact extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'customer_id', 'customer_outlet_id', 'name', 'job_title', 'phone', 'email', 'is_primary'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
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
}
