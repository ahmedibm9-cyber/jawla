<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VanTransfer extends Model
{
    protected $fillable = [
        'company_id', 'from_user_id', 'to_user_id', 'status', 'accepted_at',
    ];

    protected $casts = ['accepted_at' => 'datetime'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function fromUser(): BelongsTo { return $this->belongsTo(User::class, 'from_user_id'); }
    public function toUser(): BelongsTo { return $this->belongsTo(User::class, 'to_user_id'); }
    public function items(): HasMany { return $this->hasMany(VanTransferItem::class); }
}
