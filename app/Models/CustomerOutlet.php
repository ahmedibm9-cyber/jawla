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
 * @property int $customer_id
 * @property int|null $route_id
 * @property string $code
 * @property string $name_ar
 * @property string $name_en
 * @property string|null $phone
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CustomerOutlet extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'customer_id', 'route_id', 'code', 'name_ar', 'name_en', 'phone', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Route, $this> */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /** @return HasMany<CustomerContact, $this> */
    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    /** @return HasMany<CustomerLocation, $this> */
    public function locations(): HasMany
    {
        return $this->hasMany(CustomerLocation::class);
    }

    /** @return HasMany<CustomerAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(CustomerAssignment::class);
    }
}
