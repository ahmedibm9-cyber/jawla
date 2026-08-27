<?php

namespace App\Services;

use App\Exceptions\Domain\DomainException;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\PriceList;
use App\Models\PriceQuotation;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Services\Contracts\PricingService as PricingServiceContract;
use App\Support\Money;
use App\Support\PriceRange;
use Carbon\CarbonInterface;

class PricingService implements PricingServiceContract
{
    public function priceForRep(int $productId, int $repId, string $unitPrice): bool
    {
        $range = $this->rangeForRep($productId, $repId);

        return $range->contains(new Money($unitPrice));
    }

    public function rangeForRep(int $productId, int $repId): PriceRange
    {
        $product = Product::find($productId);

        if (! $product) {
            return new PriceRange(Money::zero(), Money::zero(), Money::zero());
        }

        $base = new Money((string) $product->price);

        $rep = User::with('roles', 'company')->find($repId);
        $isRep = $rep && $rep->can('create:invoice');

        if (! $isRep) {
            return new PriceRange($base, Money::zero(), Money::zero());
        }

        $discount = $rep->company->rep_discount_percent ?? 10;
        $minus = $base->percent((string) $discount);

        return new PriceRange($base, Money::zero(), $minus);
    }

    public function rangeForQuotation(PriceQuotation $quotation): PriceRange
    {
        $base = new Money((string) $quotation->base_price);
        $plus = new Money((string) $quotation->rep_plus);
        $minus = new Money((string) $quotation->rep_minus);

        return new PriceRange($base, $plus, $minus);
    }

    public function effectivePrice(
        int $companyId,
        int $customerId,
        int $productId,
        string $quantity,
        ?CarbonInterface $at = null,
    ): string {
        $at ??= today();
        $product = Product::whereKey($productId)->where('company_id', $companyId)->first();
        $customer = Customer::whereKey($customerId)->where('company_id', $companyId)->first();
        if (! $product || ! $customer) {
            throw new DomainException('Price resolution requires same-company product and customer.');
        }

        $price = ProductPrice::query()
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->where('min_quantity', '<=', $quantity)
            ->where(fn ($query) => $query->whereNull('valid_from')->orWhere('valid_from', '<=', $at->toDateString()))
            ->where(fn ($query) => $query->whereNull('valid_upto')->orWhere('valid_upto', '>=', $at->toDateString()))
            ->whereHas('priceList', fn ($query) => $query
                ->where('company_id', $companyId)
                ->where('type', 'selling')
                ->where('is_active', true))
            ->where(fn ($query) => $query->where('customer_id', $customerId)->orWhereNull('customer_id'))
            ->orderByRaw('CASE WHEN customer_id IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('min_quantity')
            ->orderByDesc('valid_from')
            ->value('price');

        return number_format((float) ($price ?? $product->price), 2, '.', '');
    }

    public function createCustomerOverride(
        User $manager,
        int $companyId,
        int $customerId,
        int $productId,
        string $price,
        CarbonInterface $validFrom,
        CarbonInterface $validUntil,
        string $reason,
    ): ProductPrice {
        if (! $manager->can('update:product_price') || ! $manager->hasCompanyAccess($companyId)) {
            throw new DomainException('Only an assigned sales manager may create a customer price override.');
        }
        if (trim($reason) === '' || bccomp($price, '0.00', 2) <= 0 || $validUntil->lt($validFrom)) {
            throw new DomainException('A positive price, bounded validity window, and reason are required.');
        }
        if (! Customer::whereKey($customerId)->where('company_id', $companyId)->exists()
            || ! Product::whereKey($productId)->where('company_id', $companyId)->exists()) {
            throw new DomainException('Price override product and customer must belong to the active company.');
        }

        $priceList = PriceList::firstOrCreate(
            ['company_id' => $companyId, 'type' => 'selling', 'is_default' => true],
            ['name' => 'Default Selling Price', 'is_active' => true],
        );
        $override = ProductPrice::create([
            'product_id' => $productId,
            'price_list_id' => $priceList->id,
            'price' => number_format((float) $price, 2, '.', ''),
            'customer_id' => $customerId,
            'valid_from' => $validFrom->toDateString(),
            'valid_upto' => $validUntil->toDateString(),
            'is_active' => true,
            'created_by' => $manager->id,
            'reason' => trim($reason),
            'is_customer_override' => true,
        ]);
        Activity::log('customer_price_override_created', $override, trim($reason));

        return $override;
    }
}
