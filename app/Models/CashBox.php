<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashBox extends Model
{
    use HasFactory;

    use BelongsToCompany;

    protected $fillable = ['company_id', 'user_id', 'balance'];
    protected $casts = ['balance' => 'decimal:2'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}