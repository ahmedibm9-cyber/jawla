<?php

namespace App\Filament\Resources\InstallationLicenseResource\Pages;

use App\Filament\Resources\InstallationLicenseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstallationLicenses extends ListRecords
{
    protected static string $resource = InstallationLicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(l('تثبيت ترخيص', 'Install license'))];
    }
}
