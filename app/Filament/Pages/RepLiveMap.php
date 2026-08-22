<?php

namespace App\Filament\Pages;

use App\Services\LocationPingService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Live map of on-shift reps (CG3). Plots the latest location ping per rep seen
 * in the last 30 minutes as Leaflet pins. The page polls every 30s and pushes
 * fresh points to the map via a browser event, so managers see reps move in
 * near-real-time. Read-only; company-scoped through BelongsToCompany.
 *
 * @property array $points
 */
class RepLiveMap extends Page
{
    protected string $view = 'filament.pages.rep-live-map';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-signal';

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'خريطة المندوبين المباشرة' : 'Live Rep Map';
    }

    public function getTitle(): string
    {
        return self::getNavigationLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المبيعات' : 'Sales';
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('view:rep_live_map') ?? false;
    }

    /**
     * Latest ping per on-shift rep, shaped for the map.
     *
     * @return array<int, array{name: string, lat: float, lng: float, accuracy: ?float, seen_at: string, minutes_ago: int}>
     */
    public function getPointsProperty(): array
    {
        return app(LocationPingService::class)->latestPerRep(30);
    }

    /** Push fresh points to the map (invoked by wire:poll). */
    public function broadcastPoints(): void
    {
        $this->dispatch('pings-updated', points: $this->points);
    }
}
