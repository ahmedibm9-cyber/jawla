<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory;

    use SoftDeletes;
    use Concerns\BelongsToCompany;

    protected $fillable = [
        'company_id', 'code', 'name_ar', 'name_en', 'type',
        'contact_person', 'phone', 'email', 'address',
        'payment_terms', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
}