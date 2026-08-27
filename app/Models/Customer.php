<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $route_id
 * @property int|null $customer_group_id
 * @property int|null $territory_id
 * @property int|null $price_list_id
 * @property int|null $account_manager_id
 * @property string $code
 * @property string $name_ar
 * @property string $name_en
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string $credit_limit
 * @property string $balance
 * @property bool $is_active
 * @property string $status
 * @property int|null $added_by
 * @property int|null $approved_by
 * @property string|null $approved_at
 * @property int|null $rejected_by
 * @property string|null $rejected_at
 * @property string|null $rejection_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use Concerns\BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'route_id', 'code', 'name_ar', 'name_en', 'phone',
        'address', 'latitude', 'longitude',
        'customer_group_id', 'territory_id', 'price_list_id', 'account_manager_id',
        'credit_limit', 'balance', 'is_active',
        'added_by', 'status', 'approved_by', 'approved_at',
        'rejected_by', 'rejected_at', 'rejection_reason',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'credit_limit' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<Route, $this> */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /** @return BelongsTo<CustomerGroup, $this> */
    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    /** @return BelongsTo<Territory, $this> */
    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    /** @return BelongsTo<PriceList, $this> */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    /** @return BelongsTo<User, $this> */
    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    /** @return BelongsTo<User, $this> */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return HasMany<Visit, $this> */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<ReturnRecord, $this> */
    public function returns(): HasMany
    {
        return $this->hasMany(ReturnRecord::class);
    }

    /** @return HasMany<CustomerOutlet, $this> */
    public function outlets(): HasMany
    {
        return $this->hasMany(CustomerOutlet::class);
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
