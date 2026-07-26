<?php

namespace App\Filament\Resources\ProductPriceResource\Pages;

use App\Filament\Resources\ProductPriceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductPrices extends ListRecords
{
    protected static string $resource = ProductPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
