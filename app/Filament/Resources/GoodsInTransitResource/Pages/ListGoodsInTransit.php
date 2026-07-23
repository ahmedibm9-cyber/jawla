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
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return [
            CreateAction::make()
                ->label($l('إضافة شحنة واردة', 'Add Incoming Shipment'))
                ->visible(fn () => ! auth()->user()->hasRole('executive')),
        ];
    }
}
