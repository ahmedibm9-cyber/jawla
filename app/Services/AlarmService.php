<?php

namespace App\Services;

use App\Models\Alarm;
use Illuminate\Database\Eloquent\Model;

class AlarmService implements Contracts\AlarmService
{
    public function raise(string $type, mixed $ref, string $title, string $description, string $severity): Alarm
    {
        return Alarm::create([
            'company_id' => $ref->company_id ?? 1,
            'type' => $type,
            'reference_type' => get_class($ref),
            'reference_id' => $ref->id,
            'title' => $title,
            'description' => $description,
            'severity' => $severity,
        ]);
    }

    public function acknowledge($alarm, int $userId)
    {
        return tap($alarm, fn (Alarm $a) => $a->update([
            'is_read' => true, 'read_by' => $userId, 'read_at' => now(),
        ]));
    }

    public function resolve($alarm, int $userId)
    {
        return tap($alarm, fn (Alarm $a) => $a->update([
            'is_read' => true, 'read_by' => $userId, 'read_at' => now(),
        ]));
    }
}