<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Spatie\Permission\Traits\HasRoles;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'company_id', 'name', 'email', 'phone', 'password',
        'employee_code', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vanWarehouse(): HasOne
    {
        return $this->hasOne(Warehouse::class)->where('type', 'van');
    }

    public function cashBox(): HasOne
    {
        return $this->hasOne(CashBox::class);
    }

    public function routes(): BelongsToMany
    {
        return $this->belongsToMany(Route::class, 'route_user');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
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

    public function workSessions(): HasMany
    {
        return $this->hasMany(WorkSession::class);
    }

    public function sentVanTransfers(): HasMany
    {
        return $this->hasMany(VanTransfer::class, 'from_user_id');
    }

    public function receivedVanTransfers(): HasMany
    {
        return $this->hasMany(VanTransfer::class, 'to_user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->hasAnyRole([
            'system_viewer', 'hr_admin', 'sales_manager', 'warehouse_keeper',
        ]);
    }
}
