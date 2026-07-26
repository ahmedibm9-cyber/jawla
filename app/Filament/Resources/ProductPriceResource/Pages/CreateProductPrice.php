<?php

namespace App\Filament\Resources\ProductPriceResource\Pages;

use App\Filament\Resources\ProductPriceResource;
use App\Services\Contracts\PricingService;
use Carbon\CarbonImmutable;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProductPrice extends CreateRecord
{
    protected static string $resource = ProductPriceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $manager = auth()->user();

        return app(PricingService::class)->createCustomerOverride(
            $manager,
            $manager->activeCompanyId(),
            (int) $data['customer_id'],
            (int) $data['product_id'],
            (string) $data['price'],
            CarbonImmutable::parse($data['valid_from']),
            CarbonImmutable::parse($data['valid_upto']),
            (string) $data['reason'],
        );
    }
}
