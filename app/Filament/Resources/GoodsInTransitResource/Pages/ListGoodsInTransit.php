<?php

namespace App\Filament\Resources\GoodsInTransitResource\Pages;

use App\Filament\Resources\GoodsInTransitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGoodsInTransit extends ListRecords
{
    protected static string $resource = GoodsInTransitResource::class;

    protected function getHeaderActions(): array
    {

        return [
            CreateAction::make()
                ->label(l('إضافة شحنة واردة', 'Add Incoming Shipment'))
                ->visible(fn () => auth()->user()?->can('create:goods_in_transit') ?? false),
        ];
    }
}
