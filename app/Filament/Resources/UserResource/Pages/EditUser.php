<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\RepProvisioningService;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function afterSave(): void
    {
        if ($this->record->hasRole('sales_rep')) {
            app(RepProvisioningService::class)->provision($this->record);
        }
    }
}
