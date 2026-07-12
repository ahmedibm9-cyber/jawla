<?php

namespace App\Services\Contracts;

use App\Models\Alarm;
use Illuminate\Database\Eloquent\Model;

interface AlarmService
{
    public function raise(string $type, Model $ref, string $title, string $description, string $severity): Alarm;

    public function acknowledge(Alarm $alarm, int $userId): Alarm;

    public function resolve(Alarm $alarm, int $userId): Alarm;
}