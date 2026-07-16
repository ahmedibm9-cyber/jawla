<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModeOfPayment extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'modes_of_payment';

    protected $fillable = ['company_id', 'name', 'type', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
