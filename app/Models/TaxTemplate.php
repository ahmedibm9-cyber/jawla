<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxTemplate extends Model
{
    use HasFactory;

    use BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'type', 'is_default', 'is_active'];
    protected $casts = ['is_default' => 'boolean', 'is_active' => 'boolean'];

    public function lines(): HasMany { return $this->hasMany(TaxTemplateLine::class); }
}