<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Support\ActiveCompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Request;

class Activity extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'user_id', 'type',
        'subject_type', 'subject_id',
        'description', 'properties',
        'ip_address', 'user_agent',
        'is_reversed', 'reversed_by', 'reversed_at', 'reversal_of',
    ];

    protected $casts = [
        'properties' => 'array',
        'is_reversed' => 'boolean',
        'reversed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function log(string $type, ?Model $subject = null, ?string $description = null, array $properties = []): self
    {
        $user = auth()->user();

        return self::create([
            'company_id' => app(ActiveCompanyContext::class)->id()
                ?? $user?->company_id
                ?? ($subject?->company_id ?? null),
            'user_id' => $user?->id,
            'type' => $type,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->id,
            'description' => $description,
            'properties' => $properties ?: null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
