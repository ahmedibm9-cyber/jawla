<?php

namespace App\Filament\Resources\GoodsInTransitResource\Pages;

use App\Filament\Resources\GoodsInTransitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGoodsInTransit extends EditRecord
{
    protected static string $resource = GoodsInTransitResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
