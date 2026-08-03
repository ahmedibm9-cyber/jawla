<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function productCategories(): HasMany
    {
        return $this->hasMany(ProductCategory::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReturnRecord::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function vanTransfers(): HasMany
    {
        return $this->hasMany(VanTransfer::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function organizationUnits(): HasMany
    {
        return $this->hasMany(OrganizationUnit::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
