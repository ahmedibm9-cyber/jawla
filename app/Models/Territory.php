<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\TerritoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name_ar
 * @property string $name_en
 * @property int|null $parent_id
 * @property int $lft
 * @property int $rgt
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Territory extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<TerritoryFactory> */
    use HasFactory;

    protected $fillable = ['company_id', 'name_ar', 'name_en', 'parent_id', 'lft', 'rgt', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /** @return BelongsTo<Territory, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'parent_id');
    }
}
