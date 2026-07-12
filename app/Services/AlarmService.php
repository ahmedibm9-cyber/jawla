<?php

namespace App\Services;

class AlarmService implements Contracts\AlarmService
{
    public function raise(string $type, $ref, string $title, string $description, string $severity)
    {
        // Implemented in Phase 13.
        return null;
    }

    public function acknowledge($alarm, int $userId)
    {
        // Implemented in Phase 13.
        return null;
    }

    public function resolve($alarm, int $userId)
    {
        // Implemented in Phase 13.
        return null;
    }
}