<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\TaxTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $type
 * @property bool $is_default
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TaxTemplate extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<TaxTemplateFactory> */
    use HasFactory;

    protected $fillable = ['company_id', 'name', 'type', 'is_default', 'is_active'];

    protected $casts = ['is_default' => 'boolean', 'is_active' => 'boolean'];

    /** @return HasMany<TaxTemplateLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(TaxTemplateLine::class);
    }
}
