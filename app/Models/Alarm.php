<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alarm extends Model
{
    use HasFactory;

    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'type', 'reference_type', 'reference_id',
        'title', 'description', 'severity', 'is_read', 'read_by', 'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];
}