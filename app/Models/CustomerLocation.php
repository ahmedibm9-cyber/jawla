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
 * @property string $type
 * @property string|null $label
 * @property string|null $address
 * @property string|null $latitude
 * @property string|null $longitude
 * @property int|null $geofence_radius_m
 * @property bool $is_primary
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CustomerLocation extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'customer_id', 'customer_outlet_id', 'type', 'label', 'address', 'latitude', 'longitude', 'geofence_radius_m', 'is_primary', 'is_active'];

    protected function casts(): array
    {
        return ['latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'is_primary' => 'boolean', 'is_active' => 'boolean'];
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
