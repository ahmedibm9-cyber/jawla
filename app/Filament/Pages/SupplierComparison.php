<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\PurchaseRequest;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Side-by-side evaluation of open purchase offers (PUR-4). Groups still-open
 * offers by product so purchasing can compare suppliers on price, terms, and
 * expiry before approving one in the Purchase Offers resource.
 */
class SupplierComparison extends Page
{
    protected string $view = 'filament.pages.supplier-comparison';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-scale';

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'مقارنة الموردين' : 'Supplier Comparison';
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
        return Auth::user()?->can('view_any:purchase_request') ?? false;
    }

    /**
     * Open offers grouped by product, each group sorted cheapest-first.
     *
     * @return Collection<string, array{product: Product, offers: Collection}>
     */
    public function getGroupsProperty()
    {
        return PurchaseRequest::query()
            ->whereIn('status', ['pending', 'sales_approved'])
            ->with(['product', 'supplier', 'user'])
            ->get()
            ->groupBy('product_id')
            ->map(fn ($offers) => [
                'product' => $offers->first()->product,
                'offers' => $offers->sortBy(fn ($o) => (float) $o->offered_price)->values(),
            ])
            ->sortByDesc(fn ($g) => $g['offers']->count());
    }
}
