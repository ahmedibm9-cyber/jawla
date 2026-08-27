<?php

declare(strict_types=1);

namespace App\Support;

use App\Exceptions\Domain\GeofenceViolationException;

final readonly class GpsCoordinate
{
    public function __construct(public float $lat, public float $lng)
    {
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            throw new GeofenceViolationException(
                'errors.gps.invalid',
                ['lat' => (string) $lat, 'lng' => (string) $lng],
            );
        }
    }

    public function metersTo(self $other): float
    {
        $earthRadius = 6371000.0;

        $latFrom = deg2rad($this->lat);
        $latTo = deg2rad($other->lat);
        $deltaLat = deg2rad($other->lat - $this->lat);
        $deltaLng = deg2rad($other->lng - $this->lng);

        $a = sin($deltaLat / 2) ** 2 + cos($latFrom) * cos($latTo) * sin($deltaLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function within(self $other, float $radiusMeters): bool
    {
        return $this->metersTo($other) <= $radiusMeters;
    }
}
