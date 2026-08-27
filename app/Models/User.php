<?php

namespace App\Models;

use App\Services\LicenseService;
use App\Support\ActiveCompanyContext;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $uuid
 * @property int $company_id
 * @property int|null $primary_organization_unit_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $password
 * @property string $employee_code
 * @property bool $is_active
 * @property bool $onboarding_seen
 * @property array<string, mixed>|null $preferences
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user) {
            if (blank($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });

        static::saving(function (User $user): void {
            if ($user->is_active && ($user->isDirty('is_active') || ! $user->exists)) {
                app(LicenseService::class)->assertCanActivateUser($user->exists ? (int) $user->id : null);
            }
        });

        static::created(function (User $user): void {
            $user->companies()->syncWithoutDetaching([$user->company_id]);
        });
    }

    protected $fillable = [
        'uuid', 'company_id', 'primary_organization_unit_id', 'name', 'email', 'phone', 'password',
        'employee_code', 'is_active', 'onboarding_seen', 'preferences',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'onboarding_seen' => 'boolean',
            'preferences' => 'array',
        ];
    }

    /**
     * Read a single UI preference (dot-free key) with a fallback default.
     */
    public function preference(string $key, mixed $default = null): mixed
    {
        return data_get($this->preferences, $key, $default);
    }

    /**
     * Persist a single UI preference, merging into the existing JSON.
     */
    public function setPreference(string $key, mixed $value): void
    {
        $preferences = $this->preferences ?? [];
        $preferences[$key] = $value;
        $this->preferences = $preferences;
        $this->save();
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<OrganizationUnit, $this> */
    public function primaryOrganizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnit::class, 'primary_organization_unit_id');
    }

    /** @return BelongsToMany<OrganizationUnit, $this> */
    public function organizationUnits(): BelongsToMany
    {
        return $this->belongsToMany(OrganizationUnit::class)->withPivot(['assigned_by', 'assigned_at']);
    }

    /** @return HasOne<RepresentativeProfile, $this> */
    public function representativeProfile(): HasOne
    {
        return $this->hasOne(RepresentativeProfile::class);
    }

    /** @return HasMany<Device, $this> */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /** @return BelongsToMany<Company, $this> */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)->withTimestamps();
    }

    public function hasCompanyAccess(int $companyId): bool
    {
        return (int) $this->company_id === $companyId
            || $this->companies()->whereKey($companyId)->exists();
    }

    public function activeCompanyId(): int
    {
        return app(ActiveCompanyContext::class)->id() ?? (int) $this->company_id;
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where(function (Builder $query) use ($companyId): void {
            $query->where('company_id', $companyId)
                ->orWhereHas('companies', fn (Builder $companies) => $companies->whereKey($companyId));
        });
    }

    /** @return HasOne<Warehouse, $this> */
    public function vanWarehouse(): HasOne
    {
        return $this->hasOne(Warehouse::class)->where('type', 'van');
    }

    /** @return HasOne<CashBox, $this> */
    public function cashBox(): HasOne
    {
        return $this->hasOne(CashBox::class);
    }

    /** @return BelongsToMany<Route, $this> */
    public function routes(): BelongsToMany
    {
        return $this->belongsToMany(Route::class, 'route_user');
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

    /** @return HasMany<Expense, $this> */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /** @return HasMany<WorkSession, $this> */
    public function workSessions(): HasMany
    {
        return $this->hasMany(WorkSession::class);
    }

    /** @return HasMany<VanTransfer, $this> */
    public function sentVanTransfers(): HasMany
    {
        return $this->hasMany(VanTransfer::class, 'from_user_id');
    }

    /** @return HasMany<VanTransfer, $this> */
    public function receivedVanTransfers(): HasMany
    {
        return $this->hasMany(VanTransfer::class, 'to_user_id');
    }

    /** @return HasMany<Activity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->hasAnyRole([
            'super_admin', 'admin', 'sales_manager', 'accounts', 'purchasing', 'warehouse_keeper', 'executive',
            'hr_admin', 'system_viewer', 'sales_rep', 'rep',
        ]);
    }
}
