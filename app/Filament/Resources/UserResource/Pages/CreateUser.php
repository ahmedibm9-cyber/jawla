<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Services\RepProvisioningService;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        /** @var User $record */
        $record = $this->record;
        if ($record->hasRole('sales_rep')) {
            app(RepProvisioningService::class)->provision($record);
        }
    }
}
