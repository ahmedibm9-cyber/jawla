<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\PriceListFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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
 * @property Carbon|null $deleted_at
 */
class PriceList extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<PriceListFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = ['company_id', 'name', 'type', 'is_default', 'is_active'];

    protected $casts = ['is_default' => 'boolean', 'is_active' => 'boolean'];

    /** @return HasMany<ProductPrice, $this> */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
