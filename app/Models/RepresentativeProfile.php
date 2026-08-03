<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepresentativeProfile extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'user_id', 'supervisor_id', 'job_title', 'hire_date',
        'vehicle_code', 'national_id', 'emergency_contact', 'skills',
    ];

    protected function casts(): array
    {
        return ['hire_date' => 'date', 'skills' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
