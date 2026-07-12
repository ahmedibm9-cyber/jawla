<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = ['company_id', 'name_ar', 'name_en', 'type', 'user_id', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function stocks(): HasMany { return $this->hasMany(Stock::class); }
    public function stockMovements(): HasMany { return $this->hasMany(StockMovement::class); }
}
