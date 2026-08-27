<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Services\RepProvisioningService;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function afterSave(): void
    {
        /** @var User $record */
        $record = $this->record;
        if ($record->hasRole('sales_rep')) {
            app(RepProvisioningService::class)->provision($record);
        }
    }
}
