<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name_ar
 * @property string $name_en
 * @property string|null $legal_entity
 * @property string|null $parent_company
 * @property string|null $abbr
 * @property string|null $tax_number
 * @property string|null $commercial_registration_number
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $logo_path
 * @property string $currency
 * @property string $vat_percent
 * @property string|null $bank_name
 * @property string|null $bank_account
 * @property string|null $bank_iban
 * @property string $rep_discount_percent
 * @property bool $is_active
 * @property bool $require_approved_devices
 * @property int|null $geofence_radius_m
 * @property string|null $country
 * @property bool $zatca_enabled
 * @property string|null $zatca_csid
 * @property string|null $zatca_secret
 * @property string|null $zatca_environment
 * @property bool $eta_enabled
 * @property string|null $eta_taxpayer_activity_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Warehouse> $warehouses
 * @property-read Collection<int, Route> $routes
 * @property-read Collection<int, Customer> $customers
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, ProductCategory> $productCategories
 * @property-read Collection<int, Invoice> $invoices
 * @property-read Collection<int, Payment> $payments
 * @property-read Collection<int, ReturnRecord> $returns
 * @property-read Collection<int, Expense> $expenses
 * @property-read Collection<int, VanTransfer> $vanTransfers
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, OrganizationUnit> $organizationUnits
 * @property-read Collection<int, Device> $devices
 */
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'name_ar', 'name_en', 'legal_entity', 'parent_company', 'abbr',
        'tax_number', 'commercial_registration_number',
        'address', 'phone', 'logo_path', 'currency', 'vat_percent',
        'bank_name', 'bank_account', 'bank_iban', 'rep_discount_percent', 'is_active',
        'require_approved_devices',
        'geofence_radius_m',
        'country', 'zatca_enabled', 'zatca_csid', 'zatca_secret', 'zatca_environment',
        'eta_enabled', 'eta_taxpayer_activity_code',
    ];

    protected $hidden = [
        'zatca_secret',
        'zatca_csid',
    ];

    protected $casts = [
        'vat_percent' => 'decimal:2',
        'rep_discount_percent' => 'decimal:2',
        'is_active' => 'boolean',
        'require_approved_devices' => 'boolean',
        'zatca_enabled' => 'boolean',
        'eta_enabled' => 'boolean',
    ];

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Warehouse, $this> */
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    /** @return HasMany<Route, $this> */
    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }

    /** @return HasMany<Customer, $this> */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @return HasMany<ProductCategory, $this> */
    public function productCategories(): HasMany
    {
        return $this->hasMany(ProductCategory::class);
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

    /** @return HasMany<Expense, $this> */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /** @return HasMany<VanTransfer, $this> */
    public function vanTransfers(): HasMany
    {
        return $this->hasMany(VanTransfer::class);
    }

    /** @return HasMany<Activity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /** @return HasMany<OrganizationUnit, $this> */
    public function organizationUnits(): HasMany
    {
        return $this->hasMany(OrganizationUnit::class);
    }

    /** @return HasMany<Device, $this> */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
