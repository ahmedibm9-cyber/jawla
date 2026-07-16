<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerGroup extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = ['company_id', 'name_ar', 'name_en', 'parent_id', 'lft', 'rgt', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class, 'parent_id');
    }
}
