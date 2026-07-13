<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use HasFactory;

    use Concerns\BelongsToCompany;
    protected $fillable = ['company_id', 'name_ar', 'name_en', 'region', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function users(): BelongsToMany { return $this->belongsToMany(User::class, 'route_user'); }
    public function customers(): HasMany { return $this->hasMany(Customer::class); }
    public function visits(): HasMany { return $this->hasMany(Visit::class); }
    public function workSessions(): HasMany { return $this->hasMany(WorkSession::class); }
}
