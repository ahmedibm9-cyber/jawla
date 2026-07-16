<?php

namespace App\Models;

use App\Enums\VanTransferStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VanTransfer extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id', 'from_user_id', 'to_user_id', 'status',
        'accepted_at', 'shipped_at', 'received_at', 'in_transit_warehouse_id',
    ];

    protected $casts = [
        'status' => VanTransferStatus::class,
        'accepted_at' => 'datetime',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VanTransferItem::class);
    }
}
