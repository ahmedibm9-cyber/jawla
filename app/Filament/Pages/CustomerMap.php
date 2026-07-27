<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Read-only map of the company's customers (Amendment R3 — route/visit map).
 * Plots every geolocated customer as a Leaflet pin so managers can see route
 * coverage at a glance.
 */
class CustomerMap extends Page
{
    protected string $view = 'filament.pages.customer-map';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-map';

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'خريطة العملاء' : 'Customer Map';
    }

    public function getTitle(): string
    {
        return self::getNavigationLabel();
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('view:customer_map') ?? false;
    }

    /**
     * Geolocated customers for the current company, shaped for the map.
     *
     * @return array<int, array{name: string, lat: float, lng: float, code: ?string, route: ?string}>
     */
    public function getPointsProperty(): array
    {
        return Customer::query()
            ->where('company_id', Auth::user()?->activeCompanyId())
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('route')
            ->get()
            ->map(fn (Customer $c) => [
                'name' => $c->name_ar ?: $c->name_en,
                'lat' => (float) $c->latitude,
                'lng' => (float) $c->longitude,
                'code' => $c->code,
                'route' => $c->route?->name_ar,
            ])
            ->all();
    }
}
